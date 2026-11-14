<?php
/*
Copyright (C) 2025 SBOSOFT, Serkan Özkan

This file is part of, SBOanalytics web site analytics

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
if(!defined('SBO_FILE_INCLUDED_PROPERLY')){
    http_response_code(404);
    exit;
}

/*
 Verifies the admin secret posted by the form in settings.php. Runs before any HTML output
 (see ui/index.php) so the lockout response and redirects can set headers, and always ends
 with a redirect back to the settings page (post/redirect/get, no form resubmission on refresh).
 */

SBO_CheckBruteForceLockout(false, 'adminSecret');

$secretFromEnv = getenv('SBO_ADMIN_SECRET');
if(empty($secretFromEnv)){
    header('Location: settings?adminError=notConfigured');
    return;
}

//is_string: an array input (adminSecret[]=...) must not reach hash_equals
$submittedSecret = $_POST['adminSecret'] ?? '';
if(is_string($submittedSecret) && $submittedSecret !== '' && hash_equals($secretFromEnv, $submittedSecret)){
    SBO_ClearFailedAuthAttempts('adminSecret');
    session_regenerate_id(true);
    $_SESSION['adminSecretVerified'] = 'yes';
    $_SESSION['adminCSRFToken'] = bin2hex(random_bytes(20));
    header('Location: settings');
    return;
}

SBO_RegisterFailedAuthAttempt('adminSecret');
header('Location: settings?adminError=invalid');
