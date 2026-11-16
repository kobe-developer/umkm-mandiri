-- Dijalankan sekali saat volume MySQL pertama kali dibuat.
-- Database utama (umkm_mandiri) dan user dibuat otomatis dari env MYSQL_DATABASE/MYSQL_USER.
CREATE DATABASE IF NOT EXISTS umkm_mandiri_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON umkm_mandiri_testing.* TO 'umkm_mandiri'@'%';
FLUSH PRIVILEGES;
