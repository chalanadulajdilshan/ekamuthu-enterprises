<?php
class Database
{
    private static $instance = null;

    private $host;
    private $name;
    private $user;
    private $password;

    public $DB_CON;

    private function __construct()
    {
        // Detect environment
        if ($this->isLocalServer()) {

            // Local DB
            $this->host = 'localhost';
            $this->name = '360-erp';
            $this->user = 'root';
            $this->password = '';
        } else {

            // Online DB settings
            $this->host = 'localhost';
            $this->name = 'chalcepi_ekamuthu-enterprises';
            $this->user = 'chalcepi_ekamuthu-enterprises';
            $this->password = '!}}c~bOdZR#g';
            $this->DB_CON = '';
        }

        // Create ONE connection only
        $this->DB_CON = mysqli_connect($this->host, $this->user, $this->password, $this->name);

        if (!$this->DB_CON) {
            die("Database connection failed: " . mysqli_connect_error());
        }

        // Set UTF-8 charset for Sinhala/Unicode support
        mysqli_set_charset($this->DB_CON, "utf8mb4");
    }

    // ✔ Singleton: Only 1 DB connection in full system
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Detect local or live server
    private function isLocalServer()
    {
        return in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
    }

    // Run query
    public function readQuery($query)
    {
        $result = mysqli_query($this->DB_CON, $query);

        if (!$result) {
            die("SQL Error: " . mysqli_error($this->DB_CON) . "<br>Query: " . $query);
        }

        return $result;
    }

    // Escape text
    public function escapeString($string)
    {
        return mysqli_real_escape_string($this->DB_CON, $string);
    }

    // ✔ Auto close connection when script ends
    public function __destruct()
    {
        if ($this->DB_CON) {
            mysqli_close($this->DB_CON);
        }
    }
}
