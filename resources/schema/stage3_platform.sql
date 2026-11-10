CREATE TABLE IF NOT EXISTS platform_site (
    id TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
    label VARCHAR(128) NOT NULL,
    updated_at DATETIME(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_module (
    site_id TINYINT NOT NULL,
    package_id VARCHAR(128) NOT NULL,
    state VARCHAR(32) NOT NULL,
    display_name VARCHAR(128) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    PRIMARY KEY (site_id, package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_instance (
    id CHAR(36) NOT NULL PRIMARY KEY,
    site_id TINYINT NOT NULL,
    package_id VARCHAR(128) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_module_instance_site_module (site_id, package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_mount (
    id CHAR(36) NOT NULL PRIMARY KEY,
    site_id TINYINT NOT NULL,
    module_instance_id CHAR(36) NOT NULL,
    mount_kind VARCHAR(32) NOT NULL,
    path_prefix VARCHAR(128) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_module_mount_prefix (site_id, path_prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS applied_module_migration (
    package_id VARCHAR(128) NOT NULL,
    migration_id VARCHAR(128) NOT NULL,
    checksum CHAR(64) NOT NULL,
    applied_at DATETIME(6) NOT NULL,
    PRIMARY KEY (package_id, migration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
