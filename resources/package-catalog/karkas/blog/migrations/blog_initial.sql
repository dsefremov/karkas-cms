CREATE TABLE IF NOT EXISTS blog_post (
    id CHAR(36) NOT NULL PRIMARY KEY,
    site_id TINYINT NOT NULL,
    module_instance_id CHAR(36) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    activity VARCHAR(16) NOT NULL DEFAULT 'inactive',
    content_etag CHAR(64) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    updated_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_blog_instance_slug (site_id, module_instance_id, slug),
    KEY idx_blog_activity (site_id, activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_version (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id CHAR(36) NOT NULL,
    version_seq INT UNSIGNED NOT NULL,
    snapshot_json JSON NOT NULL,
    saved_at DATETIME(6) NOT NULL,
    UNIQUE KEY uq_blog_post_version (post_id, version_seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_slug_redirect (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id CHAR(36) NOT NULL,
    from_slug VARCHAR(120) NOT NULL,
    to_slug VARCHAR(120) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    KEY idx_blog_redirect_from (from_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
