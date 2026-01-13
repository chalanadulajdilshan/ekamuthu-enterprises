<?php

class CompanyErpDatabase
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
            $this->name = 'company_erp';
            $this->user = 'root';
            $this->password = '';
        } else {
            // Online DB settings
            $this->host = 'localhost';
            $this->name = 'chalcepi_company_admin';
            $this->user = 'chalcepi_company_admin';
            $this->password = 'J.){FGdwos^R';
        }

        // Create connection
        $this->DB_CON = mysqli_connect($this->host, $this->user, $this->password, $this->name);

        if (!$this->DB_CON) {
            die("Company ERP Database connection failed: " . mysqli_connect_error());
        }
    }

    // Singleton: Only 1 DB connection for company_erp
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new CompanyErpDatabase();
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
            throw new Exception("SQL Error: " . mysqli_error($this->DB_CON) . " | Query: " . $query);
        }

        return $result;
    }

    // Escape text
    public function escapeString($string)
    {
        return mysqli_real_escape_string($this->DB_CON, $string);
    }

    private function getTableColumns($table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $result = $this->readQuery("SHOW COLUMNS FROM `{$table}`");
        $columns = [];

        while ($row = mysqli_fetch_assoc($result)) {
            if (isset($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;
    }

    private function tableExists($table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $result = $this->readQuery("SHOW TABLES LIKE '{$table}'");
        return ($result && mysqli_num_rows($result) > 0);
    }

    private function pickCustomerTableName()
    {
        $candidates = [
            'customer',
            'customers',
            'customer_master',
            'customer_details'
        ];

        foreach ($candidates as $candidate) {
            if ($this->tableExists($candidate)) {
                return $candidate;
            }
        }

        throw new Exception('Customer table not found in company_erp database. Tried: ' . implode(', ', $candidates));
    }

    private function getPrimaryKeyColumn($table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $result = $this->readQuery("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (isset($row['Column_name']) && $row['Column_name']) {
                return $row['Column_name'];
            }
        }

        return 'id';
    }

    private function pickStartDateColumn($columns)
    {
        $candidates = [
            'project_start_date',
            'start_date',
            'startdate',
            'project_start',
            'project_startdate',
            'subscription_start_date',
            'contract_start_date',
            'date_start',
            'created_at',
            'created_date'
        ];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    // Get project start date by customer ID
    public function getProjectStartDate($customerId)
    {
        $customerId = (int)$customerId;

        $table = $this->pickCustomerTableName();
        $primaryKeyColumn = $this->getPrimaryKeyColumn($table);

        $columns = $this->getTableColumns($table);
        $startDateColumn = $this->pickStartDateColumn($columns);

        if (!$startDateColumn) {
            throw new Exception(
                "Start date column not found in `{$table}`. Available columns: " . implode(', ', $columns)
            );
        }

        $query = "SELECT `{$startDateColumn}` AS project_start_date FROM `{$table}` WHERE `{$primaryKeyColumn}` = {$customerId} LIMIT 1";
        $result = $this->readQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['project_start_date'] ?? null;
        }

        return null;
    }

    // Calculate next payment due date (monthly from project start)
    public function getNextPaymentDueDate($projectStartDate)
    {
        if (!$projectStartDate) {
            return null;
        }

        $startDate = new DateTime($projectStartDate);
        $today = new DateTime();

        // If the project hasn't started yet, the first due date is the start date
        if ($today < $startDate) {
            return $startDate;
        }

        // Billing anchor = day of month of the start date
        $anchorDay = (int)$startDate->format('d');

        $year = (int)$today->format('Y');
        $month = (int)$today->format('m');

        // Helper: last day of a given month
        $getLastDayOfMonth = function ($y, $m) {
            return (int)(new DateTime(sprintf('%04d-%02d-01', $y, $m)))->format('t');
        };

        // Build due date for current month (clamped)
        $day = min($anchorDay, $getLastDayOfMonth($year, $month));
        $due = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));

        // If already passed, move to next month and clamp again
        if ($due < $today) {
            $next = new DateTime(sprintf('%04d-%02d-01', $year, $month));
            $next->modify('+1 month');
            $year = (int)$next->format('Y');
            $month = (int)$next->format('m');
            $day = min($anchorDay, $getLastDayOfMonth($year, $month));
            $due = new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
        }

        return $due;
    }

    // Get days until payment is due
    public function getDaysUntilPayment($projectStartDate)
    {
        $nextDueDate = $this->getNextPaymentDueDate($projectStartDate);

        if (!$nextDueDate) {
            return null;
        }

        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $nextDueDate->setTime(0, 0, 0);

        $interval = $today->diff($nextDueDate);

        return $interval->days;
    }

    // Get system down status by customer ID
    public function getSystemDownStatus($customerId)
    {
        $customerId = (int)$customerId;

        $query = "SELECT `system_down` FROM `customer` WHERE `id` = {$customerId} LIMIT 1";
        $result = $this->readQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['system_down'] ?? null;
        }

        return null;
    }

    // Update system down status
    public function updateSystemDownStatus($customerId, $status)
    {
        $customerId = (int)$customerId;
        $status = (int)$status;

        $table = $this->pickCustomerTableName(); // Reuse existing table logic due to 'customer' vs 'customers' etc.
        $primaryKeyColumn = $this->getPrimaryKeyColumn($table);

        $query = "UPDATE `{$table}` SET `system_down` = {$status} WHERE `{$primaryKeyColumn}` = {$customerId}";
        
        $result = mysqli_query($this->DB_CON, $query);

        if (!$result) {
            throw new Exception("SQL Error: " . mysqli_error($this->DB_CON) . " | Query: " . $query);
        }

        return true;
    }

    // Get next payment date by customer ID
    public function getNextPaymentDate($customerId)
    {
        $customerId = (int)$customerId;

        $table = $this->pickCustomerTableName();
        $primaryKeyColumn = $this->getPrimaryKeyColumn($table);
        $columns = $this->getTableColumns($table);

        if (!in_array('next_payment_date', $columns)) {
            // Fallback to null if column doesn't exist
            // Or throw exception depending on strictness requirements
            return null;
        }

        $query = "SELECT `next_payment_date` FROM `{$table}` WHERE `{$primaryKeyColumn}` = {$customerId} LIMIT 1";
        $result = $this->readQuery($query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['next_payment_date'] ?? null;
        }

        return null;
    }

    // Get days remaining until a specific date
    public function getDaysRemaining($targetDate)
    {
        if (!$targetDate) {
            return null;
        }

        $target = new DateTime($targetDate);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $target->setTime(0, 0, 0);

        $interval = $today->diff($target);
        
        // If target is in the past, return negative days
        if ($today > $target) {
            return -1 * $interval->days;
        }

        return $interval->days;
    }

    // Auto close connection when script ends
    public function __destruct()
    {
        if ($this->DB_CON) {
            mysqli_close($this->DB_CON);
        }
    }
}
