USE sboanalytics;

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