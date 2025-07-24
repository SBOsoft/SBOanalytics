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


$domainId = (int)($_REQUEST['domainId'] ?? 0);
if(!$domainId){
    SBO_API_Error_Response(400, 'Invalid domainId parameter', 'domainId parameter is required');
    return;
}

$limit = (int)($_REQUEST['limit'] ?? 20);
if($limit >1000){
    $limit = 1000;
}
$page = (int)($_REQUEST['page'] ?? 1);
if($page >1000){
    $page = 1;
}

$now = new DateTimeImmutable();

//twStart and twEnd will have '2025-07-11 11:00' format
$twStart = trim($_REQUEST['twStart'] ?? '');
if(!$twStart){
    $twStart = $now->modify('-1 day')->format('Y-m-d H:i:s');
}
$twEnd = trim($_REQUEST['twEnd'] ?? '');
if(!$twEnd){
    $twEnd = $now->format('Y-m-d H:i:s');
}



$keyName = trim($_REQUEST['keyName'] ?? '');
$keyValue = trim($_REQUEST['keyValue'] ?? '');


$sqlParams = array();

$sql = 'SELECT host_id as hostId, request_ts as requestTimestamp, client_ip as clientIP, '
        . 'remote_user as remoteUser, http_method as method, path3 as basePath, request_uri as requestUri, '
        . 'http_status as statusCode, bytes_sent as bytesSent, '
        . 'referer, is_malicious as isMalicious, '
        . 'ua_string as uaString, ua_os as uaOS, ua_family as uaFamily, '
        . 'ua_device_type as deviceType, ua_is_human as isHuman , ua_intent as requestIntent ';

$sql.= ' FROM sbo_rawlogs '
        . ' WHERE '
        . 'domain_id=:domainId '
        . ' AND request_ts BETWEEN :twStartTS AND :twEndTS ';
$sqlParams[':domainId'] = $domainId;
$sqlParams[':twStartTS'] = $twStart;
$sqlParams[':twEndTS'] = $twEnd;
switch($keyName){
    case 'clientIP':
        $sql.=' AND client_ip=INET6_ATON(:clientIP) ';
        $sqlParams[':clientIP'] = $keyValue;        
        break;
    case 'method':
        $sql.=' AND http_method=:method ';
        $sqlParams[':method'] = $keyValue;        
        break;
    case 'basePath':
        $sql.=' AND path3 LIKE :path ';
        $sqlParams[':path'] = $keyValue.'%';        
        break;
    case 'statusCode':
        $sql.=' AND http_status=:statusCode ';
        $sqlParams[':statusCode'] = $keyValue;        
        break;
    case 'referer':
        $sql.=' AND referer=:referer ';
        $sqlParams[':referer'] = $keyValue;        
        break;
    case 'isMalicious':
        $sql.=' AND is_malicious=:isMalicious ';
        $sqlParams[':isMalicious'] = $keyValue;        
        break;
    case 'uaOS':
        $sql.=' AND ua_os=:uaOS ';
        $sqlParams[':uaOS'] = $keyValue;        
        break;
    case 'uaFamily':
        $sql.=' AND ua_family=:uaFamily ';
        $sqlParams[':uaFamily'] = $keyValue;        
        break;
    case 'deviceType':
        $sql.=' AND ua_device_type=:deviceType ';
        $sqlParams[':deviceType'] = $keyValue;        
        break;
    case 'isHuman':
        $sql.=' AND ua_is_human=:isHuman ';
        $sqlParams[':isHuman'] = $keyValue;        
        break;
    case 'requestIntent':
        $sql.=' AND ua_intent=:requestIntent ';
        $sqlParams[':requestIntent'] = $keyValue;        
        break;
}

$sql.= ' ORDER BY request_ts';
$sql.= ' LIMIT :limit OFFSET :offset';
$sqlParams[':limit'] = ($limit+1);
$sqlParams[':offset'] = ($page - 1) * $limit;
//echo $sql;
//print_r($sqlParams);
SBO_API_ResponseStart($limit, $page);
$hasMoreResults = false;

SBO_DB_Query($sql, $sqlParams, $limit, $hasMoreResults, 'SBO_PrintQueryResultRowAsJson');

SBO_API_ResponseEnd($hasMoreResults);
