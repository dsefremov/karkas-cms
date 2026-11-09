CREATE TABLE IF NOT EXISTS platform_install_state (
    singleton_id TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
    status VARCHAR(32) NOT NULL,
    current_step VARCHAR(64) NULL,
    updated_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_maintenance (
    singleton_id TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    reason VARCHAR(255) NULL,
    updated_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_operation (
    id CHAR(36) NOT NULL PRIMARY KEY,
    operation_type VARCHAR(64) NOT NULL,
    status VARCHAR(32) NOT NULL,
    phase VARCHAR(64) NULL,
    actor_label VARCHAR(128) NULL,
    plan_digest CHAR(64) NULL,
    payload JSON NOT NULL,
    error_message TEXT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    INDEX idx_platform_operation_status (status),
    INDEX idx_platform_operation_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_installation (
    package_id VARCHAR(128) NOT NULL PRIMARY KEY,
    version VARCHAR(64) NOT NULL,
    manifest_digest CHAR(64) NOT NULL,
    staging_relative_path VARCHAR(512) NOT NULL,
    lifecycle_status VARCHAR(32) NOT NULL,
    installed_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS deployment_rollback_slot (
    slot_key VARCHAR(32) NOT NULL PRIMARY KEY,
    generation_id VARCHAR(64) NOT NULL,
    core_version VARCHAR(64) NOT NULL,
    plan_digest CHAR(64) NOT NULL,
    module_versions JSON NOT NULL,
    saved_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS platform_core_state (
    singleton_id TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
    version VARCHAR(64) NOT NULL,
    manifest_digest CHAR(64) NOT NULL,
    staging_relative_path VARCHAR(512) NOT NULL,
    updated_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
