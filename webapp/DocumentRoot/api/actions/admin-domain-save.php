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

$domainId = (int) filter_input(INPUT_POST, 'domainId', FILTER_SANITIZE_NUMBER_INT);
$domainName = filter_input(INPUT_POST, 'domainName', FILTER_VALIDATE_DOMAIN, array('default' => null, 'flags'=>FILTER_FLAG_HOSTNAME));
$description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW, array('default' => null));
$timeWindowSizeMinutes = (int) filter_input(INPUT_POST, 'timeWindowSizeMinutes', FILTER_SANITIZE_NUMBER_INT);

if(empty($domainName)){
    SBO_API_Error_Response(400, 'Invalid domain name', 'Domain name is invalid');
    exit;
}
if($domainId>0){
    $sql='UPDATE sbo_domains SET domain_name=:domainName, description=:description, timeWindowSizeMinutes=:timeWindowSizeMinutes '
            . 'WHERE domain_id=:domainId ';
    $sqlParams = array(':domainName'=>$domainName, 
                    ':description'=>$description, 
                    ':timeWindowSizeMinutes'=>$timeWindowSizeMinutes,
                    ':domainId'=>$domainId);
    $saveResult = SBO_DB_InsertUpdateDelete($sql, $sqlParams);

    $msg = array('succeeded'=>$saveResult);
    echo json_encode($msg);    

}
else{
    $sql='INSERT INTO sbo_domains (domain_name, description, created, timeWindowSizeMinutes)  '.
            ' VALUES (:domainName, :description, now(), :timeWindowSizeMinutes)';

    $sqlParams = array(':domainName'=>$domainName, 
                    ':description'=>$description, 
                    ':timeWindowSizeMinutes'=>$timeWindowSizeMinutes);
    $lastInsertId = SBO_DB_InsertUpdateDelete($sql, $sqlParams, true);
    http_response_code(201);
    $msg = array('domainId'=>$lastInsertId);
    echo json_encode($msg);    
}