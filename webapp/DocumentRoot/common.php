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

//naive brute-force throttling for login/admin-secret forms, session based
define('SBO_BRUTE_FORCE_MAX_ATTEMPTS', 10);
define('SBO_BRUTE_FORCE_BASE_LOCKOUT_SECONDS', 30);
define('SBO_BRUTE_FORCE_MAX_LOCKOUT_SECONDS', 900);
define('SBO_SESSION_COOKIE_PATH', getenv('SBO_SESSION_COOKIE_PATH'));

//metric time window sizes (minutes) supported by SBOLogProcessor
define('SBO_ALLOWED_TIME_WINDOW_SIZES', array(1, 5, 10, 15, 30, 60));

//exception code used when the database connection cannot be established
define('SBO_ERROR_DB_UNAVAILABLE', 1001);

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
    //the constant is always defined, it is false/empty when the environment variable is not set
    if(empty(SBO_AUTH_TYPE)){
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

function SBO_CheckIfUserIsAdmin(){
    if(empty($_SESSION['adminSecretVerified']) ||  $_SESSION['adminSecretVerified']!=='yes'){
        SBO_API_Error_Response(401, 'Authorization required', 'You are not authorized to access to this endpoint');
        exit;
    }
}

function SBO_CheckAdminCSRF(){
    $csrfToken = filter_input(INPUT_POST, 'csrfToken', FILTER_VALIDATE_REGEXP, array('default' => null, 'options' => array('regexp' => '/^([a-zA-Z0-9]+)$/')));
    $sessionToken = $_SESSION['adminCSRFToken'] ?? '';
    if(empty($sessionToken) || !hash_equals($sessionToken, (string)$csrfToken)){
        SBO_API_Error_Response(400, 'Bad request', 'Invalid CSRF token');
        exit;
    }
}

/**
 * Sets hardened session cookie params before starting the session. Must be called
 * instead of a bare session_start() from every entry point (ui/index.php, api/index.php).
 */
function SBO_StartSession(){
    session_name('sboanalytics');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => !empty(SBO_SESSION_COOKIE_PATH) ? SBO_SESSION_COOKIE_PATH : '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Session-keyed brute-force throttle for the login and admin-secret forms. Not a
 * substitute for proxy/WAF-level rate limiting (see README), but blunts naive
 * automated guessing without requiring any new infrastructure.
 */
function SBO_CheckBruteForceLockout($isApi, $keyPrefix){
    $lockedUntil = $_SESSION[$keyPrefix.'LockedUntil'] ?? 0;
    $remaining = $lockedUntil - time();
    if($remaining > 0){
        header('Retry-After: '.$remaining);
        if($isApi){
            SBO_API_Error_Response(429, 'Too many attempts', 'Too many failed attempts. Please try again later.');
        }
        else{
            http_response_code(429);
            echo 'Too many failed attempts. Please try again later.';
        }
        exit;
    }
}

function SBO_RegisterFailedAuthAttempt($keyPrefix){
    $failCount = ($_SESSION[$keyPrefix.'FailCount'] ?? 0) + 1;
    $_SESSION[$keyPrefix.'FailCount'] = $failCount;
    if($failCount >= SBO_BRUTE_FORCE_MAX_ATTEMPTS){
        $lockSeconds = min(SBO_BRUTE_FORCE_MAX_LOCKOUT_SECONDS, SBO_BRUTE_FORCE_BASE_LOCKOUT_SECONDS * (2 ** ($failCount - SBO_BRUTE_FORCE_MAX_ATTEMPTS)));
        $_SESSION[$keyPrefix.'LockedUntil'] = time() + $lockSeconds;
    }
}

function SBO_ClearFailedAuthAttempts($keyPrefix){
    unset($_SESSION[$keyPrefix.'FailCount']);
    unset($_SESSION[$keyPrefix.'LockedUntil']);
}

/**
 * Parses a date/time request value. Separators are ignored, so '2025-07-11 11:00',
 * '2025-07-11T11:00' (datetime-local inputs), '2025-07-11 11:00:30', '2025-07-11' and
 * '202507111100' are all accepted. Missing parts are filled so that an end value covers the
 * whole day/minute it names: a date only is 00:00:00 (start) or 23:59:59 (end), missing
 * seconds are 00 (start) or 59 (end). E.g. the log viewer's minute precision end '17:59'
 * includes requests up to 17:59:59.
 * Returns a DateTimeImmutable, or null if the value is not a valid date/time.
 */
function SBO_ParseDateTimeParam($strValue, $isEnd){
    $digits = preg_replace('/[^0-9]+/', '', (string)$strValue);
    switch(strlen($digits)){
        case 8:
            $digits .= $isEnd ? '235959' : '000000';
            break;
        case 12:
            $digits .= $isEnd ? '59' : '00';
            break;
        case 14:
            break;
        default:
            return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!YmdHis', $digits);
    //reject overflowing values like 2025-02-30 which DateTime would silently roll over
    if(!$dt || $dt->format('YmdHis') !== $digits){
        return null;
    }
    return $dt;
}

function SBO_HtmlEncode($str){
    return htmlspecialchars($str, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
}

function SBO_JsonEncode($str){
    echo json_encode($str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
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
 * Returns the shared PDO connection, connecting on first use.
 * Throws RuntimeException with code SBO_ERROR_DB_UNAVAILABLE when the database is not reachable
 * (the api turns that into a 503 response, see SBO_API_ExceptionHandler in api/index.php).
 * @global PDO $SBO_DB_PDO_INSTANCE
 * @return PDO
 */
function SBO_DB_GetConnection(){
    global $SBO_DB_PDO_INSTANCE;
    if($SBO_DB_PDO_INSTANCE === null){
        SBO_DB_Connect();
    }
    if($SBO_DB_PDO_INSTANCE === false){
        throw new RuntimeException('Database connection is not available', SBO_ERROR_DB_UNAVAILABLE);
    }
    return $SBO_DB_PDO_INSTANCE;
}

/**
 * Prepares and executes $sql with named $params and returns the PDOStatement.
 * Database errors are thrown as PDOException (PDO::ERRMODE_EXCEPTION).
 * @return PDOStatement
 */
function SBO_DB_Execute($sql, $params){
    $stmt = SBO_DB_GetConnection()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Returns up to $limit rows as an array. $hasMore is set to true when the query returned more
 * than $limit rows (select $limit+1 rows to detect that).
 * For paged api responses use SBO_API_StreamQuery (api/index.php) instead.
 * @return array
 */
function SBO_DB_Query($sql, $params, $limit, &$hasMore) {
    $hasMore = false;
    $results = array();
    $stmt = SBO_DB_Execute($sql, $params);
    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        if(count($results) >= $limit){
            $hasMore = true;
            break;
        }
        $results[] = $row;
    }
    $stmt->closeCursor();
    return $results;
}

/**
 * Executes an INSERT/UPDATE/DELETE. Returns the last insert id when $returnLastInsertId is true,
 * otherwise true. Database errors are thrown as PDOException.
 */
function SBO_DB_InsertUpdateDelete($sql, $params, $returnLastInsertId=false){
    SBO_DB_Execute($sql, $params);
    if($returnLastInsertId){
        return SBO_DB_GetConnection()->lastInsertId();
    }
    return true;
}

/**
 * True when $e is a MySQL duplicate key error (unique/primary key violation, error 1062).
 */
function SBO_DB_IsDuplicateKeyError($e){
    return ($e instanceof PDOException) && (($e->errorInfo[1] ?? null) === 1062);
}

/**
 * Escapes LIKE wildcards in $value for use with ESCAPE '!' (e.g. "col LIKE :p ESCAPE '!'").
 * '!' is used instead of backslash because backslash handling depends on the sql_mode.
 */
function SBO_DB_EscapeLike($value){
    return str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), $value);
}