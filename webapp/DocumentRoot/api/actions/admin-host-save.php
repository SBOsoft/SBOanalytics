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

$hostId = (int) filter_input(INPUT_POST, 'hostId', FILTER_SANITIZE_NUMBER_INT);
$hostName = filter_input(INPUT_POST, 'hostName', FILTER_VALIDATE_DOMAIN, array('default' => null, 'flags'=>FILTER_FLAG_HOSTNAME));
$description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW, array('default' => null));

if(empty($hostName)){
    SBO_API_Error_Response(400, 'Invalid host name', 'Host name is invalid');
    exit;
}
if($hostId>0){
    $sql='UPDATE sbo_hosts SET host_name=:hostName, description=:description '
            . 'WHERE host_id=:hostId ';
    $sqlParams = array(':hostName'=>$hostName, 
                    ':description'=>$description, 
                    ':hostId'=>$hostId);
    $saveResult = SBO_DB_InsertUpdateDelete($sql, $sqlParams);

    $msg = array('succeeded'=>$saveResult);
    echo json_encode($msg);    

}
else{
    $sql='INSERT INTO sbo_hosts (host_name, description, created)  '.
            ' VALUES (:hostName, :description, now())';

    $sqlParams = array(':hostName'=>$hostName, 
                    ':description'=>$description);
    $lastInsertId = SBO_DB_InsertUpdateDelete($sql, $sqlParams, true);
    http_response_code(201);
    $msg = array('hostId'=>$lastInsertId);
    echo json_encode($msg);    
}