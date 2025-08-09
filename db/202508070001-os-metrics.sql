
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