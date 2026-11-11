ALTER TABLE blog_post
    ADD FULLTEXT INDEX ft_blog_search (title, body_text);

ALTER TABLE site_page
    ADD FULLTEXT INDEX ft_page_search (title, body_text);
