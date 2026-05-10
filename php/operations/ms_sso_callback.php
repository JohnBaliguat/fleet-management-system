<?php
// Microsoft (Entra ID) SSO — step 2: exchange the code for tokens,
// fetch the profile from Microsoft Graph, match an existing user row
// (or auto-provision if configured), set the PHP session, redirect
// the user to their role's dashboard.

session_start();

$cfg = __DIR__ . '/../config/microsoft_sso.php';
if (!file_exists($cfg)) {
    header("Location: login?error=" . urlencode('Microsoft SSO not configured.'));
    exit;
}
require_once $cfg;
require_once __DIR__ . '/../config/config.php';

function ms_fail(string $reason): void {
    header("Location: login?error=" . urlencode($reason));
    exit;
}

$state = $_GET['state'] ?? '';
$code  = $_GET['code']  ?? '';
$err   = $_GET['error_description'] ?? ($_GET['error'] ?? '');

if ($err)                                                 ms_fail('Microsoft sign-in failed: ' . $err);
if ($code === '' || $state === '')                        ms_fail('Microsoft sign-in callback was malformed.');
if (!hash_equals($_SESSION['ms_sso_state'] ?? '', $state)) ms_fail('Microsoft sign-in state mismatch — please try again.');
unset($_SESSION['ms_sso_state']);   // state is single-use.

$tenant = MS_TENANT_ID ?: 'common';
$tokenUrl = 'https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/token';
$post = [
    'client_id'     => MS_CLIENT_ID,
    'client_secret' => MS_CLIENT_SECRET,
    'code'          => $code,
    'redirect_uri'  => MS_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
    'scope'         => 'openid profile email offline_access User.Read',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST            => true,
    CURLOPT_POSTFIELDS      => http_build_query($post),
    CURLOPT_RETURNTRANSFER  => true,
    CURLOPT_HTTPHEADER      => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT         => 15,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($resp === false)                ms_fail('Could not reach Microsoft: ' . $cerr);
$tok = json_decode($resp, true);
if ($http >= 400 || !is_array($tok)) ms_fail('Microsoft token exchange failed: ' . substr($resp, 0, 200));

$accessToken = $tok['access_token'] ?? '';
$idToken     = $tok['id_token']     ?? '';
if ($accessToken === '')             ms_fail('Microsoft did not return an access token.');

// Decode the id_token claims for oid + tid + email + name (no
// signature verification — we'll cross-check oid via Graph below).
function ms_jwt_claims(string $jwt): array {
    $parts = explode('.', $jwt);
    if (count($parts) < 2) return [];
    $payload = $parts[1];
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
    $json = base64_decode(strtr($payload, '-_', '+/'), true);
    $arr = json_decode($json ?: 'null', true);
    return is_array($arr) ? $arr : [];
}
$idClaims = $idToken !== '' ? ms_jwt_claims($idToken) : [];

// Fetch the canonical profile from Graph so we trust Microsoft's view.
$ch = curl_init('https://graph.microsoft.com/v1.0/me');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken, 'Accept: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$me = ($resp !== false) ? json_decode($resp, true) : null;
if ($http >= 400 || !is_array($me)) ms_fail('Microsoft Graph /me failed: ' . substr((string)$resp, 0, 200));

$oid       = (string)($me['id']                ?? $idClaims['oid']                 ?? '');
$tid       = (string)($idClaims['tid']         ?? '');
$msEmail   = strtolower(trim((string)($me['mail'] ?? $me['userPrincipalName'] ?? $idClaims['email'] ?? $idClaims['preferred_username'] ?? '')));
$fullName  = (string)($me['displayName']       ?? $idClaims['name']                ?? '');
$givenName = (string)($me['givenName']         ?? $idClaims['given_name']          ?? '');
$surname   = (string)($me['surname']           ?? $idClaims['family_name']         ?? '');

if ($oid === '' || $msEmail === '') ms_fail('Microsoft profile did not return enough info to log you in.');

// 1) Match by Microsoft OID first (fastest, survives email changes).
$user = null;
$stmt = $conn->prepare("SELECT * FROM user WHERE microsoft_oid = ? LIMIT 1");
$stmt->bind_param("s", $oid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2) Fall back to email match (and bind OID for next time).
if (!$user) {
    $stmt = $conn->prepare("SELECT * FROM user WHERE LOWER(user_email) = ? LIMIT 1");
    $stmt->bind_param("s", $msEmail);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user) {
        $stmt = $conn->prepare("UPDATE user SET microsoft_oid = ?, microsoft_tenant_id = ?, microsoft_email = ?, microsoft_linked_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("sssi", $oid, $tid, $msEmail, $user['user_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// 3) Optionally auto-provision a row for users from an allow-listed tenant.
if (!$user && defined('MS_AUTO_PROVISION_TENANT') && MS_AUTO_PROVISION_TENANT !== '' && $tid === MS_AUTO_PROVISION_TENANT) {
    $role  = defined('MS_AUTO_PROVISION_ROLE') ? MS_AUTO_PROVISION_ROLE : 'Visual';
    $uname = explode('@', $msEmail)[0];
    $stmt = $conn->prepare(
        "INSERT INTO user (user_name, user_fname, user_lname, user_mname, user_assignLocation, user_email, user_pass, user_type, user_image, user_accountStat, user_code, microsoft_oid, microsoft_tenant_id, microsoft_email, microsoft_linked_at)
         VALUES (?, ?, ?, '', '', ?, '', ?, '', 'Approve', 0, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("sssssssss", $uname, $givenName, $surname, $msEmail, $role, $oid, $tid, $msEmail);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    if ($newId) {
        $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $newId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$user)                                  ms_fail('Your Microsoft account (' . $msEmail . ') is not authorised. Ask an admin to create your user row first.');
if ($user['user_accountStat'] === 'Pending') ms_fail('Your account is pending approval.');

// Set the session and redirect to the role-specific dashboard.
$_SESSION['user_id']     = (int)$user['user_id'];
$_SESSION['user_type']   = $user['user_type'];
$_SESSION['user_name']   = $user['user_name'];
$_SESSION['ms_authed']   = true;

$dest = 'dashboard';
switch ($user['user_type']) {
    case 'Admin':       $dest = 'dashboard';          break;
    case 'Dispatcher':  $dest = 'dispatch-dashboard'; break;
    case 'Shop':        $dest = 'shop-dashboard';     break;
    case 'User':        $dest = 'hr-dashboard';       break;
    case 'Rescue':      $dest = 'shop-dashboard';     break;
    case 'HR-Admin':    $dest = 'hra-dashboard';      break;
    case 'Visual':      $dest = 'visual-dashboard';   break;
    case 'Gate-Guard':  $dest = 'gate-dashboard';     break;
}
header('Location: ' . $dest);
exit;
