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

//api responses are JSON, PHP warnings/notices must never be written into them. They are still logged.
ini_set('display_errors', '0');

define('SBO_API_MAX_LIMIT', 1000);
define('SBO_API_MAX_PAGE', 1000);

function SBO_API_Error_Response($statusCode, $errorTitle, $errorDetails){
    http_response_code($statusCode);
    $msg = array('error'=>$errorTitle, 'description'=>$errorDetails);
    echo json_encode($msg);
}

/**
 * Sends an error response and ends the request.
 */
function SBO_API_ExitWithError($statusCode, $errorTitle, $errorDetails){
    SBO_API_Error_Response($statusCode, $errorTitle, $errorDetails);
    exit;
}

/**
 * Uncaught exceptions (database errors etc.) become a JSON error response. Details go to the
 * server error log only, never to the client.
 */
function SBO_API_ExceptionHandler($e){
    error_log('SBOanalytics api error: '.get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
    //drop partial output that is still buffered, it would make the JSON invalid
    while(ob_get_level() > 0){
        ob_end_clean();
    }
    if(headers_sent()){
        //part of the response was already sent, nothing useful can be added
        return;
    }
    if($e->getCode() === SBO_ERROR_DB_UNAVAILABLE){
        SBO_API_Error_Response(503, 'Service unavailable', 'Database is not available');
    }
    else{
        SBO_API_Error_Response(500, 'Internal error', 'An unexpected error occurred, details are in the server error log');
    }
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

/**
 * Reads the limit and page request parameters.
 * limit: 1..SBO_API_MAX_LIMIT, $defaultLimit when missing or less than 1.
 * page: 1..SBO_API_MAX_PAGE.
 * @return array [$limit, $page]
 */
function SBO_API_GetPaging($defaultLimit){
    $limit = (int)($_REQUEST['limit'] ?? 0);
    if($limit < 1){
        $limit = $defaultLimit;
    }
    $page = (int)($_REQUEST['page'] ?? 1);
    return array(min($limit, SBO_API_MAX_LIMIT), min(max($page, 1), SBO_API_MAX_PAGE));
}

/**
 * Reads a date/time request parameter (formats: see SBO_ParseDateTimeParam).
 * Returns $default when the parameter is missing or empty, ends the request with a 400 response
 * when it is present but invalid.
 * @return DateTimeImmutable
 */
function SBO_API_GetDateTimeParam($name, $isEnd, $default){
    $raw = $_REQUEST[$name] ?? '';
    if(!is_string($raw) || trim($raw) === ''){
        return $default;
    }
    $dt = SBO_ParseDateTimeParam($raw, $isEnd);
    if(!$dt){
        SBO_API_ExitWithError(400, 'Invalid '.$name.' parameter', $name.' must be a date and time like 2025-07-11 11:00');
    }
    return $dt;
}

/**
 * Ends the request with a 400 response when $start is after $end.
 */
function SBO_API_CheckTimeRange($start, $end){
    if($start > $end){
        SBO_API_ExitWithError(400, 'Invalid time range', 'twStart must not be after twEnd');
    }
}

/**
 * Runs a paged SELECT and writes the standard response:
 * {"limit":..,"page":..,"data":[row,row,...,null],"hasMoreResults":true|false}
 * The data array always ends with a null entry (client code skips it).
 * $sql must end with 'LIMIT :limit OFFSET :offset', the values are set here: limit+1 rows are
 * fetched to detect whether more results exist.
 * The query is executed before anything is written, so database errors still produce a clean
 * JSON error response. Rows are then written one by one instead of being collected in an array.
 */
function SBO_API_StreamQuery($sql, $params, $limit, $page){
    $params[':limit'] = $limit + 1;
    $params[':offset'] = ($page - 1) * $limit;
    $stmt = SBO_DB_Execute($sql, $params);

    SBO_API_ResponseStart($limit, $page);
    echo '[';
    $numRows = 0;
    $hasMoreResults = false;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $numRows++;
        if($numRows > $limit){
            $hasMoreResults = true;
            break;
        }
        echo json_encode($row, JSON_INVALID_UTF8_SUBSTITUTE), ',';
    }
    $stmt->closeCursor();
    echo 'null]';
    SBO_API_ResponseEnd($hasMoreResults);
}

header('Content-type: application/json');
set_exception_handler('SBO_API_ExceptionHandler');

SBO_StartSession();
//No api action writes to the session. Release the session lock right away, otherwise parallel
//requests from the same browser (the dashboard sends ~11 at once) wait for each other.
//$_SESSION stays readable after this call, so the auth/admin/CSRF checks below still work.
session_write_close();

SBO_CheckIsAuthenticated(true);

$action = $_REQUEST['act'] ?? '';

switch($action){
    case 'domains':
        include('actions/domains.php');
        break;
    case 'logs':
        include('actions/logs.php');
        break;
    case 'metrics':
        include('actions/metrics.php');
        break;
    case 'hosts':
        include('actions/hosts.php');
        break;
    case 'os-metrics':
        include('actions/os-metrics.php');
        break;
    case 'admin-host-save':
        SBO_CheckIfUserIsAdmin();
        SBO_CheckAdminCSRF();
        include('actions/admin-host-save.php');
        break;
    case 'admin-domain-save':
        SBO_CheckIfUserIsAdmin();
        SBO_CheckAdminCSRF();
        include('actions/admin-domain-save.php');
        break;
    default:
        SBO_API_Error_Response(400, 'Unknown action', 'Value of act parameter is not one of the expected values');
        break;

}
