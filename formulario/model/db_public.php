<?php
// model/db_public.php
class DatabasePublic {
    private $host = "db";
    private $db_name = "db4ftndih4hblv";
    private $username = "root";
    private $password = "root";
    public $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }
}