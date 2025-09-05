<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'cloude_drive';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch(PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
        return $this->conn;
    }

    public function close() {
        $this->conn = null;
    }
}

// Global database connection function
function getDBConnection() {
    $database = new Database();
    return $database->connect();
}

// Configuration constants
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024 * 1024); // 5GB
define('CHUNK_SIZE', 1024 * 1024); // 1MB chunks
define('DEFAULT_USER_STORAGE_LIMIT', 10 * 1024 * 1024 * 1024); // 10GB default
define('ADMIN_STORAGE_LIMIT', 100 * 1024 * 1024 * 1024); // 100GB for admins
define('MAX_STORAGE_LIMIT', 1024 * 1024 * 1024 * 1024); // 1TB max configurable
// Allow all file types - no restrictions
define('ALLOWED_EXTENSIONS', []); // Empty array means all extensions are allowed

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>
