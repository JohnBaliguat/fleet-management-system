<?php
// Microsoft (Entra ID) SSO — step 1: redirect the user to Microsoft's
// authorize endpoint with a CSRF-resistant state cookie.

session_start();

$cfg = __DIR__ . '/../config/microsoft_sso.php';
if (!file_exists($cfg)) {
    header("Location: login?error=" . urlencode('Microsoft SSO is not configured. Copy microsoft_sso.example.php to microsoft_sso.php and fill in the Azure values.'));
    exit;
}
require_once $cfg;

if (!defined('MS_CLIENT_ID') || MS_CLIENT_ID === 'YOUR_APP_CLIENT_ID') {
    header("Location: login?error=" . urlencode('Microsoft SSO is not configured. See php/config/microsoft_sso.example.php.'));
    exit;
}

// Random opaque state to defeat login-CSRF.
$state = bin2hex(random_bytes(16));
$nonce = bin2hex(random_bytes(16));
$_SESSION['ms_sso_state'] = $state;
$_SESSION['ms_sso_nonce'] = $nonce;

$tenant = MS_TENANT_ID ?: 'common';
$authorizeUrl = 'https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/authorize?'
    . http_build_query([
        'client_id'     => MS_CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri'  => MS_REDIRECT_URI,
        'response_mode' => 'query',
        'scope'         => 'openid profile email offline_access User.Read',
        'state'         => $state,
        'nonce'         => $nonce,
        'prompt'        => 'select_account',
    ]);

header('Location: ' . $authorizeUrl);
exit;
