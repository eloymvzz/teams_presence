<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config['teams'] = [
    // Azure AD tenant ID (GUID)
    'tenant_id' => '',
    // Azure AD app (client) ID
    'client_id' => '',
    // Azure AD app client secret
    'client_secret' => '',
    // Microsoft Graph base URL
    'graph_base_url' => 'https://graph.microsoft.com/v1.0',
    // OAuth2 token URL
    'token_url' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
];
