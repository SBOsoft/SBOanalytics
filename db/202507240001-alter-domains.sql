USE sboanalytics;

ALTER TABLE sbo_domains ADD COLUMN timeWindowSizeMinutes int not null default 10;


