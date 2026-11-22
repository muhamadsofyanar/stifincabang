CREATE TABLE IF NOT EXISTS stifin_official_report_rows (
    branch_code VARCHAR(40) NOT NULL,
    report_type VARCHAR(24) NOT NULL,
    source_ref VARCHAR(100) COLLATE utf8mb4_bin NOT NULL,
    report_date DATE NOT NULL,
    promoter_code VARCHAR(40) NOT NULL,
    report_value VARCHAR(120) NOT NULL,
    imported_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (branch_code, report_type, source_ref),
    KEY report_period (branch_code, report_type, report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
