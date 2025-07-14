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

$username = ($_POST['username'] ?? null);
$password = ($_POST['password'] ?? null);

switch(SBO_AUTH_TYPE){
    case 'single':
        //don't allow empty username or password
        if(!empty($username) && !empty($password) && $username===SBO_AUTH_SINGLE_USER && $password === SBO_AUTH_SINGLE_PWD){
            session_regenerate_id(true);
            $_SESSION['authOK'] = true;
            $_SESSION['authenticatedUsername'] = $username;
            header('Location: dashboard?authOk=1');
            return;
        }
        break;
}

header('Location: auth-form?authError=1');