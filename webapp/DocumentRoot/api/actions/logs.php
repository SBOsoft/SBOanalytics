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
    SBO_API_ExitWithError(400, 'Invalid domainId parameter', 'domainId parameter is required');
}

list($limit, $page) = SBO_API_GetPaging(20);

$now = new DateTimeImmutable();

//twStart and twEnd are like '2025-07-11 11:00' or '2025-07-11 11:00:59'
$twStartDt = SBO_API_GetDateTimeParam('twStart', false, $now->modify('-1 day'));
$twEndDt = SBO_API_GetDateTimeParam('twEnd', true, $now);
SBO_API_CheckTimeRange($twStartDt, $twEndDt);
$twStart = $twStartDt->format('Y-m-d H:i:s');
$twEnd = $twEndDt->format('Y-m-d H:i:s');


$keyName = trim($_REQUEST['keyName'] ?? '');
$keyValue = trim($_REQUEST['keyValue'] ?? '');


$sqlParams = array();

$sql = 'SELECT host_id as hostId, request_ts as requestTimestamp, INET6_NTOA(client_ip) as clientIP, '
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
    case '':
        //no filter
        break;
    case 'clientIP':
        $sql.=' AND client_ip=INET6_ATON(:clientIP) ';
        $sqlParams[':clientIP'] = $keyValue;
        break;
    case 'remoteUser':
        $sql.=' AND remote_user=:remoteUser ';
        $sqlParams[':remoteUser'] = $keyValue;
        break;
    case 'method':
        $sql.=' AND http_method=:method ';
        $sqlParams[':method'] = $keyValue;
        break;
    case 'basePath':
        //the path itself, or anything below it: '/blog' matches '/blog' and '/blog/2025/post'
        //but not '/blogger'. The root path '/' (and an empty value) match exactly, otherwise
        //'/' would match every request.
        if($keyValue === '' || $keyValue === '/'){
            $sql.=' AND path3=:path ';
            $sqlParams[':path'] = $keyValue;
        }
        else{
            $sql.=" AND (path3=:path OR path3 LIKE :pathPrefix ESCAPE '!') ";
            $sqlParams[':path'] = $keyValue;
            $prefix = str_ends_with($keyValue, '/') ? $keyValue : $keyValue.'/';
            $sqlParams[':pathPrefix'] = SBO_DB_EscapeLike($prefix).'%';
        }
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
    default:
        SBO_API_ExitWithError(400, 'Invalid keyName parameter', 'keyName is not one of the supported filter names');
}

$sql.= ' ORDER BY request_ts';
$sql.= ' LIMIT :limit OFFSET :offset';

SBO_API_StreamQuery($sql, $sqlParams, $limit, $page);
