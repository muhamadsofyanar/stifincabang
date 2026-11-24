-- STIFIn: atribusi checkout dan Canvas akses produk. Jalankan pada database aktif.
SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'utm_source'),
    'DO 1',
    'ALTER TABLE orders ADD COLUMN utm_source VARCHAR(100) DEFAULT NULL'
);
PREPARE stifin_stmt FROM @ddl;
EXECUTE stifin_stmt;
DEALLOCATE PREPARE stifin_stmt;

SET @ddl := IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'access_canvas_id'),
    'DO 1',
    'ALTER TABLE products ADD COLUMN access_canvas_id INT UNSIGNED DEFAULT NULL'
);
PREPARE stifin_stmt FROM @ddl;
EXECUTE stifin_stmt;
DEALLOCATE PREPARE stifin_stmt;

ALTER TABLE products MODIFY COLUMN access_type ENUM('course','embed','canvas') NOT NULL DEFAULT 'course';
ALTER TABLE canvas_pages MODIFY COLUMN page_type
    ENUM('standalone','homepage','product_landing','rotator','lead_magnet_landing','redirect','product_access')
    NOT NULL DEFAULT 'standalone';
