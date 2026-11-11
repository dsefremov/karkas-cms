-- Stage 7 blog facade: bind page tree entry to /blog public URL.
ALTER TABLE module_mount
    ADD COLUMN bound_page_id CHAR(36) NULL AFTER path_prefix;

ALTER TABLE module_instance
    ADD COLUMN last_facade_page_id CHAR(36) NULL AFTER package_id;
