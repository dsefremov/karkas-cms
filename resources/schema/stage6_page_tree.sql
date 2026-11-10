CREATE TABLE IF NOT EXISTS site_page (
    id CHAR(36) NOT NULL PRIMARY KEY,
    site_id TINYINT UNSIGNED NOT NULL,
    parent_id CHAR(36) NOT NULL,
    slug VARCHAR(64) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    title VARCHAR(255) NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    activity VARCHAR(16) NOT NULL DEFAULT 'inactive',
    full_path VARCHAR(512) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_site_page_path (site_id, full_path),
    KEY idx_site_page_parent (site_id, parent_id, sort_order, id),
    KEY idx_site_page_site (site_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_page_redirect (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    site_id TINYINT UNSIGNED NOT NULL,
    page_id CHAR(36) NOT NULL,
    from_path VARCHAR(512) NOT NULL,
    to_path VARCHAR(512) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_site_page_redirect_from (site_id, from_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
