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

$hostId = (int) filter_input(INPUT_GET, 'hostId', FILTER_SANITIZE_NUMBER_INT);

if(!$hostId){
    SBO_API_Error_Response(400, 'Invalid hostId parameter', 'hostId parameter is required');
    return;
}


$limit = (int) filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
if($limit<1){
    $limit = 50;
}
if($limit >1000){
    $limit = 1000;
}
$page = (int) filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
if($page<1){
    $page = 1;
}
if($page >1000){
    $page = 1;
}


$twStart = filter_input(INPUT_GET, 'twStart', FILTER_VALIDATE_REGEXP, 
        array('default' => null, 'options' => array('regexp' => '/^([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2})$/')));


$twEnd = filter_input(INPUT_GET, 'twEnd', FILTER_VALIDATE_REGEXP, 
        array('default' => null, 'options' => array('regexp' => '/^([0-9]{4}-[0-9]{2}-[0-9]{2}\s[0-9]{2}:[0-9]{2})$/')));

//supported values: empty string: no grouping. hour: by hour, day:by day, month: by month
$groupBy = trim($_REQUEST['groupBy'] ?? '');

$groupBy = filter_input(INPUT_GET, 'groupBy', FILTER_UNSAFE_RAW);

$sqlParams = array();



$sql = 'SELECT  ';
$sqlAvgPart = 'ROUND(AVG(up_duration_minutes),2) as hostUptimeMinutes, '
            . 'ROUND(AVG(users),2) as loggedInUsers, '
            . 'ROUND(AVG(load_average1),2) as loadAverage1, '
            . 'ROUND(AVG(load_average5),2) as  loadAverage5, '
            . 'ROUND(AVG(load_average15),2) as  loadAverage15, '
            . 'ROUND(AVG(swap_use),2) as  swapUsed,'
            . 'ROUND(AVG(cache_use),2) as cacheUsed, '
            . 'ROUND(AVG(memory_use),2) as memoryUsed,'
            . 'ROUND(AVG(memory_free),2) as memoryFree ';
switch($groupBy){
    case 'hour':
        $sql.='  DATE(metrics_ts) as day, HOUR(metrics_ts) as hour, ';
        $sql.=$sqlAvgPart;
        break;
    case 'day':
        $sql.='  DATE(metrics_ts) as day,  ';
        $sql.=$sqlAvgPart;
        break;
    case 'month':
        $sql.='  YEAR(metrics_ts) as year, MONTH(metrics_ts) as month,  ';
        $sql.=$sqlAvgPart;
        break;
    default:
        $sql.= ' metrics_ts as metricTS, up_duration_minutes as hostUptimeMinutes, '
            . 'users as loggedInUsers, '
            . 'load_average1 as loadAverage1, '
            . 'load_average5 as  loadAverage5, '
            . 'load_average15 as  loadAverage15, '
            . 'swap_use as  swapUsed,'
            . 'cache_use as cacheUsed, '
            . 'memory_use as memoryUsed,'
            . 'memory_free as memoryFree ';
}
$sql.= ' FROM sbo_os_metrics '
        . ' WHERE '
        . 'host_id=:hostId '
        . ' AND metrics_ts>=:twStart AND metrics_ts<=:twEnd ';

$sqlParams[':hostId'] = $hostId;
$sqlParams[':twStart'] = $twStart;
$sqlParams[':twEnd'] = $twEnd;

switch($groupBy){
    case 'hour':
        $sql.=' GROUP BY DATE(metrics_ts), HOUR(metrics_ts) ';
        break;
    case 'day':
        $sql.=' GROUP BY DATE(metrics_ts) ';
        break;
    case 'month':
        $sql.=' GROUP BY YEAR(metrics_ts), MONTH(metrics_ts) ';
        break;
    default:
        $sqlAvgPart = '';
        break;
}
$sql.= ' LIMIT :limit OFFSET :offset';

$sqlParams[':limit'] = ($limit+1);
$sqlParams[':offset'] = ($page - 1) * $limit;

SBO_API_ResponseStart($limit, $page);
$hasMoreResults = false;

SBO_DB_Query($sql, $sqlParams, $limit, $hasMoreResults, 'SBO_PrintQueryResultRowAsJson');

SBO_API_ResponseEnd($hasMoreResults);
