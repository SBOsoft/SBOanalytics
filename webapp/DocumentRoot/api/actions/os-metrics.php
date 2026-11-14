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

$hostId = (int)($_REQUEST['hostId'] ?? 0);
if(!$hostId){
    SBO_API_ExitWithError(400, 'Invalid hostId parameter', 'hostId parameter is required');
}

list($limit, $page) = SBO_API_GetPaging(50);

$now = new DateTimeImmutable();

//twStart and twEnd are like '2025-07-11 11:00', default is the last 24 hours
$twStartDt = SBO_API_GetDateTimeParam('twStart', false, $now->modify('-24 hours'));
$twEndDt = SBO_API_GetDateTimeParam('twEnd', true, $now);
SBO_API_CheckTimeRange($twStartDt, $twEndDt);

//supported values: empty string: no grouping. hour: by hour, day:by day, month: by month
$groupBy = trim($_REQUEST['groupBy'] ?? '');

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
            . 'ROUND(AVG(memory_free),2) as memoryFree, '
            . 'ROUND(AVG(memory_available),2) as memoryAvailable ';
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
    case '':
        $sql.= ' metrics_ts as metricTS, up_duration_minutes as hostUptimeMinutes, '
            . 'users as loggedInUsers, '
            . 'load_average1 as loadAverage1, '
            . 'load_average5 as  loadAverage5, '
            . 'load_average15 as  loadAverage15, '
            . 'swap_use as  swapUsed,'
            . 'cache_use as cacheUsed, '
            . 'memory_use as memoryUsed,'
            . 'memory_free as memoryFree,'
            . 'memory_available as memoryAvailable ';
        break;
    default:
        SBO_API_ExitWithError(400, 'Invalid groupBy parameter', 'groupBy must be empty or one of hour, day, month');
}
$sql.= ' FROM sbo_os_metrics '
        . ' WHERE '
        . 'host_id=:hostId '
        . ' AND metrics_ts>=:twStart AND metrics_ts<=:twEnd ';

$sqlParams[':hostId'] = $hostId;
$sqlParams[':twStart'] = $twStartDt->format('Y-m-d H:i:s');
$sqlParams[':twEnd'] = $twEndDt->format('Y-m-d H:i:s');

//explicit time order, charts and paging rely on it
switch($groupBy){
    case 'hour':
        $sql.=' GROUP BY DATE(metrics_ts), HOUR(metrics_ts) ';
        $sql.=' ORDER BY DATE(metrics_ts), HOUR(metrics_ts) ';
        break;
    case 'day':
        $sql.=' GROUP BY DATE(metrics_ts) ';
        $sql.=' ORDER BY DATE(metrics_ts) ';
        break;
    case 'month':
        $sql.=' GROUP BY YEAR(metrics_ts), MONTH(metrics_ts) ';
        $sql.=' ORDER BY YEAR(metrics_ts), MONTH(metrics_ts) ';
        break;
    default:
        $sql.=' ORDER BY metrics_ts ';
        break;
}
$sql.= ' LIMIT :limit OFFSET :offset';

SBO_API_StreamQuery($sql, $sqlParams, $limit, $page);
