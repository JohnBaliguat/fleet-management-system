<?php
// Microsoft (Entra ID) work-account SSO config.
//
// Copy this file to `microsoft_sso.php` and paste in the values from
// your Azure App Registration. `microsoft_sso.php` is intentionally
// NOT committed to git (it contains a client secret).
//
// Setup steps (one-time, in Azure Portal):
//   1. Azure Portal → Microsoft Entra ID → App registrations → New
//      registration.
//        Name:         Pantrucks Fleet
//        Supported:    Accounts in this organisational directory only
//                      (single tenant)  OR  Multi-tenant — pick what
//                      matches your company policy.
//        Redirect URI: Web — http://localhost/Fleet%20Management/index.php?route=ms-callback
//                      (for production replace with your https URL)
//   2. Copy the "Application (client) ID" → MS_CLIENT_ID below.
//   3. Copy the "Directory (tenant) ID"    → MS_TENANT_ID below.
//      Use 'common' here if you registered a multi-tenant app and
//      want anyone with a Microsoft work account to be able to try.
//   4. Certificates & secrets → New client secret → copy the *value*
//      (not the ID) → MS_CLIENT_SECRET below.
//   5. API permissions → ensure Microsoft Graph "User.Read" is granted
//      (it's added by default).

if (!defined('MS_TENANT_ID'))     define('MS_TENANT_ID',     'YOUR_TENANT_GUID_OR_common');
if (!defined('MS_CLIENT_ID'))     define('MS_CLIENT_ID',     'YOUR_APP_CLIENT_ID');
if (!defined('MS_CLIENT_SECRET')) define('MS_CLIENT_SECRET', 'YOUR_APP_CLIENT_SECRET');

// Redirect URI must match exactly what's registered in Azure.
if (!defined('MS_REDIRECT_URI')) {
    define('MS_REDIRECT_URI', 'http://localhost/Fleet%20Management/index.php?route=ms-callback');
}

// Optional: allow auto-provisioning of new user rows for any Microsoft
// user whose tenant matches MS_ALLOWED_TENANT. Leave empty to disable
// auto-provision (then only pre-existing user.user_email matches log in).
if (!defined('MS_AUTO_PROVISION_TENANT')) define('MS_AUTO_PROVISION_TENANT', '');
// New auto-provisioned users land with this role until an admin promotes them.
if (!defined('MS_AUTO_PROVISION_ROLE'))   define('MS_AUTO_PROVISION_ROLE',   'Visual');
