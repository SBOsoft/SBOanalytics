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

//API 
define('SBO_FILE_INCLUDED_PROPERLY', true);
include('../common.php');

function SBO_API_Error_Response($statusCode, $errorTitle, $errorDetails){
    http_response_code($statusCode);
    $msg = array('error'=>$errorTitle, 'description'=>$errorDetails);
    echo json_encode($msg);
}

function SBO_API_ResponseStart($limit, $page){
    echo '{"limit":'.$limit.',"page":'.$page.',"data":';
}

function SBO_API_ResponseEnd($hasMoreResults){
    echo ',"hasMoreResults":';
    if($hasMoreResults){
        echo 'true';
    }
    else{
        echo 'false';
    }
    echo '}';
}

SBO_Authenticate();

header('Content-type: application/json');

$action = $_REQUEST['act'];

switch($action){
    case 'domains':
        include('actions/domains.php');
        break;
    case 'metrics':
        include('actions/metrics.php');
        break;
    default:
        SBO_API_Error_Response(400, 'Unknown action', 'Value of act parameter is not one of the expected values');        
        break;
    
}