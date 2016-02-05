ALTER TABLE sites
ADD COLUMN url VARCHAR(255) DEFAULT NULL AFTER name;

UPDATE sites
JOIN roles ON sites.id = roles.site_id
JOIN users ON users.id = roles.user_id
SET sites.url = users.url;
