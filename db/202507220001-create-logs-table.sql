USE sboanalytics;

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