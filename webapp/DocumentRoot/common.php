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
///////////config

define('SBO_AUTH_TYPE', getenv('SBO_AUTH_TYPE'));
define('SBO_DB_HOST', getenv('SBO_DB_HOST'));
define('SBO_DB_NAME', getenv('SBO_DB_NAME'));
define('SBO_DB_USER', getenv('SBO_DB_USER'));
define('SBO_DB_PASSWORD', getenv('SBO_DB_PASSWORD'));
define('SBO_AUTH_SINGLE_USER', getenv('SBO_AUTH_SINGLE_USER'));
define('SBO_AUTH_SINGLE_PWD', getenv('SBO_AUTH_SINGLE_PWD'));

//database connection
$SBO_DB_PDO_INSTANCE = null;




//////////////common functions
/** 
 * SBO_AUTH_TYPE environment variable MUST be set to one of the following values
 * - none
 * - single
 * TODO support for More types will be added later
 * 
 */
function SBO_CheckIsAuthenticated($isApi){
    if(!defined('SBO_AUTH_TYPE')){
        if($isApi){
            SBO_API_Error_Response(500, 'Invalid application configuration', 'Authentication type is not defined in configuration');
            exit;
        }
        else{
            die('Invalid application configuration. Authentication type is not defined');
        }
    }
    switch(SBO_AUTH_TYPE){
        case 'none':
            //nothing
            break;
        case 'single':
            if($_SESSION['authOK'] ?? false){
                //ok
            }
            else{
                if($isApi){
                    SBO_API_Error_Response(401, 'Authorization required', 'You are not authorized to access to this endpoint');
                }
                else{
                    header('Location: auth-form');
                }
                exit;
            }
            break;
        default:
            if($isApi){
                SBO_API_Error_Response(500, 'Invalid application configuration', 'Invalid authentication type');
                exit;
            }
            else{
                die('Invalid application configuration. Invalid authentication type');
            }
            break;
    }
}

function SBO_PrintQueryResultRowAsJson($row){
    echo json_encode($row);
}

function SBO_ParseTimeWindow($strValue){
    if(empty($strValue)){
        return null;
    }
    return preg_replace('/[^0-9]+/', '', $strValue);
    
}

function SBO_HtmlEncode($str){
    return htmlspecialchars($str, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
}

function SBO_JsonEncode($str){
    echo json_encode($str);
}

//////// DB
function SBO_DB_Connect(){
    global $SBO_DB_PDO_INSTANCE;
    $host = getenv('SBO_DB_HOST');
    $db = getenv('SBO_DB_NAME');
    $user = getenv('SBO_DB_USER');
    $pass = getenv('SBO_DB_PASSWORD');
    $charset = 'utf8mb4';       // Character set for connection
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        //TODO is this necessary, suggested by gemini ?
        PDO::ATTR_EMULATE_PREPARES   => false
    ];
    
    try {
        $SBO_DB_PDO_INSTANCE = new PDO($dsn, $user, $pass, $options);
    } 
    catch (\PDOException $e) {
        error_log('Failed to create PDO connection to database. host:'.$host.' db:'.$db.' user:'.$user.' error:'.$e->getMessage());
        $SBO_DB_PDO_INSTANCE = false;
    }
}

/**
 * When $callbackFunction is set, an array will be printed to output and always a null entry will be added to the 
 * end of the json array. even if no results are found there will be an null entry in the output array
 * @global PDO $SBO_DB_PDO_INSTANCE
 * @param string $sql
 * @param array $params
 * @param function $callbackFunction
 * @return boolean|int|array
 */
function SBO_DB_Query($sql, $params, $limit, &$hasMore, $callbackFunction = null) {
    global $SBO_DB_PDO_INSTANCE;
    if($SBO_DB_PDO_INSTANCE === null){
        SBO_DB_Connect();
    }
    if($SBO_DB_PDO_INSTANCE === false){
        return false;
    }
    $numrows = 0;
    $results = array();
    $stmt = $SBO_DB_PDO_INSTANCE->prepare($sql);
    if($stmt->execute($params)){
        if($callbackFunction){
            echo '[';
        }
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
            $numrows++;
            if($numrows > $limit){
                $hasMore = true;
                break;
            }
            if($callbackFunction){
                $callbackFunction($row);
                echo ',';
            }
            else{
                $results[] = $row;
            }
        }   //while
        $stmt->closeCursor();
        if($callbackFunction){
            echo 'null]';
            return $numrows;
        }
        else{
            return $results;
        }
    }
    else{
        return false;
    }
    
}