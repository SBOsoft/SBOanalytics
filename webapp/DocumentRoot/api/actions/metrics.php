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

$metricType = (int)($_REQUEST['metricType'] ?? 0);
if(!$metricType){
    SBO_API_Error_Response(400, 'Invalid metricType parameter', 'metricType parameter is required');
    return;
}

$limit = (int)($_REQUEST['limit'] ?? 50);
if($limit >1000){
    $limit = 1000;
}
$page = (int)($_REQUEST['page'] ?? 1);
if($page >1000){
    $page = 1;
}

$now = new DateTimeImmutable();

//twStart and twEnd will have '2025-07-11 11:00' format
$twStart = SBO_ParseTimeWindow(trim($_REQUEST['twStart'] ?? ''));
if(!$twStart){
    $twStart = $now->modify('-1 month')->format('YmdHi');
}
$twEnd = SBO_ParseTimeWindow(trim($_REQUEST['twEnd'] ?? ''));
if(!$twEnd){
    $twEnd = $now->format('YmdHi');
}



$keyValue = trim($_REQUEST['keyValue'] ?? '');

//supported values: empty string: no grouping. hour: by hour, day:by day, month: by month, key: by keyValue
$groupBy = trim($_REQUEST['groupBy'] ?? '');
$sqlParams = array();

$sql = 'SELECT metric_type as metricType, key_value as keyValue, ';
switch($groupBy){
    case 'hour':
        $sql.='  (time_window DIV 100) as tw, SUM(metric_value) as metric  ';
        break;
    case 'day':
        $sql.='  (time_window DIV 10000) as tw, SUM(metric_value) as metric  ';
        break;
    case 'month':
        $sql.='  (time_window DIV 1000000) as tw, SUM(metric_value) as metric  ';
        break;
    case 'key':
        $sql.=' SUM(metric_value) as metric ';
        break;
    default:
        $sql.=' time_window as tw,  metric_value as metric';
}
$sql.= ' FROM sbo_metrics '
        . ' WHERE '
        . 'domain_id=:domainId '
        . ' AND metric_type=:metricType '
        . ' AND time_window>=:twStart AND time_window<=:twEnd ';
if($keyValue){
    $sql.=' AND key_value=:keyValue ';
    $sqlParams[':keyValue'] = $keyValue;    
}
$sqlParams[':domainId'] = $domainId;
$sqlParams[':metricType'] = $metricType;
$sqlParams[':twStart'] = $twStart;
$sqlParams[':twEnd'] = $twEnd;

//timeWindow values are like 202507061130, dividde by 100 to get hour, by 10000 to get day, 1000000 to get month
switch($groupBy){
    case 'hour':
        $sql.=' GROUP BY metric_type, key_value, tw';
        break;
    case 'day':
        $sql.=' GROUP BY metric_type, key_value, tw';
        break;
    case 'month':
        $sql.=' GROUP BY metric_type, key_value, tw';
        break;
    case 'key':
        $sql.=' GROUP BY metric_type, key_value';
        break;
}
$sql.= ' ORDER BY metric_type, key_value ';
$sql.= ' LIMIT :limit OFFSET :offset';
$sqlParams[':limit'] = ($limit+1);
$sqlParams[':offset'] = ($page - 1) * $limit;
//echo $sql;
//print_r($sqlParams);
SBO_API_ResponseStart($limit, $page);
$hasMoreResults = false;

SBO_DB_Query($sql, $sqlParams, $limit, $hasMoreResults, 'SBO_PrintQueryResultRowAsJson');

SBO_API_ResponseEnd($hasMoreResults);
