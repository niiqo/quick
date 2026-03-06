<?php
// model/db_public.php
class DatabasePublic {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $pdo;

    public function __construct() {
        try {
            $this->host = getenv('DB_HOST') ?: 'db';
            $this->port = getenv('DB_PORT') ?: '3306';
            $this->db_name = getenv('DB_NAME') ?: 'db4ftndih4hblv';
            $this->username = getenv('DB_USER') ?: 'root';
            $this->password = getenv('DB_PASS') ?: 'root';

            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $attempts = 60;
            while ($attempts > 0) {
                try {
                    $this->pdo = new PDO($dsn, $this->username, $this->password);
                    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return;
                } catch (PDOException $e) {
                    $attempts--;
                    if ($attempts === 0) {
                        throw $e;
                    }
                    sleep(1);
                }
            }
        } catch(PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
