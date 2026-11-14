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

$metricType = (int)($_REQUEST['metricType'] ?? 0);
if(!$metricType){
    SBO_API_ExitWithError(400, 'Invalid metricType parameter', 'metricType parameter is required');
}

list($limit, $page) = SBO_API_GetPaging(50);

$now = new DateTimeImmutable();

//time_window values are like 202507061130 (YYYYMMDDHHMM). A date-only twEnd covers the whole day.
$twStartDt = SBO_API_GetDateTimeParam('twStart', false, $now->modify('-1 month'));
$twEndDt = SBO_API_GetDateTimeParam('twEnd', true, $now);
SBO_API_CheckTimeRange($twStartDt, $twEndDt);
$twStart = $twStartDt->format('YmdHi');
$twEnd = $twEndDt->format('YmdHi');

$keyValue = trim($_REQUEST['keyValue'] ?? '');

//supported values: empty string: no grouping. hour: by hour, day:by day, month: by month, key: by keyValue
$groupBy = trim($_REQUEST['groupBy'] ?? '');
$sqlParams = array();

//time_window values are like 202507061130, divide by 100 to get hour, by 10000 to get day, 1000000 to get month
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
    case '':
        $sql.=' time_window as tw,  metric_value as metric';
        break;
    default:
        SBO_API_ExitWithError(400, 'Invalid groupBy parameter', 'groupBy must be empty or one of hour, day, month, key');
}
$sql.= ' FROM sbo_metrics '
        . ' WHERE '
        . 'domain_id=:domainId '
        . ' AND metric_type=:metricType '
        . ' AND time_window>=:twStart AND time_window<=:twEnd ';
//compare with '' so a key value of "0" (e.g. is-human=0) still filters
if($keyValue !== ''){
    $sql.=' AND key_value=:keyValue ';
    $sqlParams[':keyValue'] = $keyValue;
}
$sqlParams[':domainId'] = $domainId;
$sqlParams[':metricType'] = $metricType;
$sqlParams[':twStart'] = $twStart;
$sqlParams[':twEnd'] = $twEnd;

switch($groupBy){
    case 'hour':
    case 'day':
    case 'month':
        $sql.=' GROUP BY metric_type, key_value, tw';
        $sql.=' ORDER BY metric_type, key_value, tw ';
        break;
    case 'key':
        //top keys first, e.g. top referers/paths
        $sql.=' GROUP BY metric_type, key_value';
        $sql.=' ORDER BY metric DESC, key_value ';
        break;
    default:
        //explicit time order, charts and labels rely on it
        $sql.=' ORDER BY metric_type, key_value, tw ';
        break;
}
$sql.= ' LIMIT :limit OFFSET :offset';

SBO_API_StreamQuery($sql, $sqlParams, $limit, $page);
