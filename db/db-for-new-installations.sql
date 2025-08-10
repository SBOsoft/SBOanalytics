/*
--Run the following if you didn't already create a database

CREATE DATABASE sboanalytics 
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sboanalytics;
*/


CREATE TABLE sbo_domains (
    domain_id INT AUTO_INCREMENT,
    domain_name VARCHAR(255) NOT NULL,
    description VARCHAR(255) null,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (domain_name),
    UNIQUE KEY sbo_domains_id (domain_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE sbo_users (
    user_id INT AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (email),
    UNIQUE KEY sbo_users_id (user_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE sbo_log_files (
    file_id int not null AUTO_INCREMENT,
    domain_id int not null,
    host_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (domain_id, host_name, file_path),
    UNIQUE KEY sbo_log_files_file_id (file_id)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE sbo_metrics (
    domain_id int not null,
    metric_type int not null,
    key_value varchar(100) not null,
    time_window bigint not null,
    metric_value bigint not null,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (domain_id, metric_type, time_window, key_value),
    KEY sbo_metrics_time (domain_id, time_window, metric_type, key_value, metric_value)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



CREATE TABLE sbo_rawlogs (
    domain_id int not null,
    host_id int not null,
    request_ts datetime not null,
    client_ip varbinary(16) default null,
    remote_user varchar(100) not null,
    http_method varchar(20) not null,
    path3 varchar(100) not null,
    request_uri varchar(100) not null,
    http_status int not null,
    bytes_sent int not null,
    referer varchar(100) default null,
    is_malicious tinyint not null default 0,
    ua_string varchar(100) default null,
    ua_os varchar(20) default null,
    ua_family varchar(20) default null,
    ua_device_type varchar(20) default null,
    ua_is_human varchar(20) default null,
    ua_intent varchar(20) default null,

    KEY sbo_rawlogs_method (domain_id, request_ts, http_method),
    KEY sbo_rawlogs_status (domain_id, request_ts, http_status),
    KEY sbo_rawlogs_referer (domain_id, request_ts, referer),
    KEY sbo_rawlogs_ua_os (domain_id, request_ts, ua_os),
    KEY sbo_rawlogs_ua_family (domain_id, request_ts, ua_family),
    KEY sbo_rawlogs_ua_device_type (domain_id, request_ts, ua_device_type),
    KEY sbo_rawlogs_ua_is_human (domain_id, request_ts, ua_is_human),
    KEY sbo_rawlogs_ua_intent (domain_id, request_ts, ua_intent),
    KEY sbo_rawlogs_client_ip (domain_id, request_ts, client_ip),
    KEY sbo_rawlogs_path3 (domain_id, request_ts, path3)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE sbo_domains ADD COLUMN timeWindowSizeMinutes int not null default 10;




CREATE TABLE sbo_os_metrics(
    host_id int not null,
    metrics_ts datetime not null,
    up_duration_minutes int not null default 0,
    users int not null default 0,
    load_average1 double not null default 0,
    load_average5 double not null default 0,
    load_average15 double not null default 0,

    swap_use bigint not null default 0,
    cache_use bigint not null default 0,
    memory_use bigint not null default 0,
    memory_free bigint not null default 0,

    PRIMARY KEY sbo_rawlogs_method (host_id, metrics_ts)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE sbo_hosts(
    host_id int not null AUTO_INCREMENT,
    host_name VARCHAR(255) NOT NULL,
    description VARCHAR(255) null,
    created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (host_id),
    UNIQUE KEY sbo_hosts_name (host_name)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


ALTER TABLE sbo_os_metrics ADD COLUMN memory_available bigint not null default 0;

/* end of 2025.08.10 up to this point */