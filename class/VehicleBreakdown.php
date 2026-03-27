<?php

class VehicleBreakdown
{
    public $id;
    public $vehicle_id;
    public $breakdown_date;
    public $issue_description;
    public $resolved_date;
    public $status;
    public $created_at;
    public $created_by;

    public function __construct($id = null)
    {
        if ($id) {
            $db = Database::getInstance();
            $id = (int)$id;
            $query = "SELECT * FROM vehicle_breakdowns WHERE id = {$id}";
            $result = mysqli_fetch_array($db->readQuery($query));
            if ($result) {
                $this->id = $result['id'];
                $this->vehicle_id = $result['vehicle_id'];
                $this->breakdown_date = $result['breakdown_date'];
                $this->issue_description = $result['issue_description'];
                $this->resolved_date = $result['resolved_date'];
                $this->status = $result['status'];
                $this->created_at = $result['created_at'];
                $this->created_by = $result['created_by'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();

        $vehicle_id = (int)$this->vehicle_id;
        $breakdown_date = mysqli_real_escape_string($db->DB_CON, $this->breakdown_date);
        $issue_description = mysqli_real_escape_string($db->DB_CON, $this->issue_description);
        $resolved_date = $this->resolved_date ? "'" . mysqli_real_escape_string($db->DB_CON, $this->resolved_date) . "'" : "NULL";
        $status = mysqli_real_escape_string($db->DB_CON, $this->status ?: 'Pending');
        $created_by = (int)$this->created_by;

        $query = "INSERT INTO vehicle_breakdowns (vehicle_id, breakdown_date, issue_description, resolved_date, status, created_at, created_by) VALUES (" .
            "{$vehicle_id}, " .
            "'{$breakdown_date}', " .
            "'{$issue_description}', " .
            "{$resolved_date}, " .
            "'{$status}', " .
            "NOW(), {$created_by})";

        $result = $db->readQuery($query);
        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        }
        return false;
    }

    public function update()
    {
        if (!$this->id) return false;
        $db = Database::getInstance();

        $id = (int)$this->id;
        $vehicle_id = (int)$this->vehicle_id;
        $breakdown_date = mysqli_real_escape_string($db->DB_CON, $this->breakdown_date);
        $issue_description = mysqli_real_escape_string($db->DB_CON, $this->issue_description);
        $resolved_date = $this->resolved_date ? "'" . mysqli_real_escape_string($db->DB_CON, $this->resolved_date) . "'" : "NULL";
        $status = mysqli_real_escape_string($db->DB_CON, $this->status);

        $query = "UPDATE vehicle_breakdowns SET " .
            "vehicle_id={$vehicle_id}, " .
            "breakdown_date='{$breakdown_date}', " .
            "issue_description='{$issue_description}', " .
            "resolved_date={$resolved_date}, " .
            "status='{$status}' " .
            "WHERE id={$id}";

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        if (!$this->id) return false;
        $db = Database::getInstance();
        $id = (int)$this->id;
        $query = "DELETE FROM vehicle_breakdowns WHERE id={$id}";
        return $db->readQuery($query) ? true : false;
    }

    public function all()
    {
        $db = Database::getInstance();
        // SQL query with downtime calculation
        $query = "SELECT vb.*, v.vehicle_no, v.ref_no AS vehicle_ref_no,
                  TIMESTAMPDIFF(MINUTE, vb.breakdown_date, COALESCE(vb.resolved_date, NOW())) as downtime_minutes
                  FROM vehicle_breakdowns vb 
                  INNER JOIN vehicles v ON vb.vehicle_id = v.id 
                  ORDER BY vb.breakdown_date DESC";
        
        $result = $db->readQuery($query);
        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            // Format downtime for display
            $mins = $row['downtime_minutes'];
            $days = floor($mins / 1440);
            $hours = floor(($mins % 1440) / 60);
            $rmins = $mins % 60;
            
            $row['downtime_formatted'] = ($days > 0 ? "{$days}d " : "") . "{$hours}h {$rmins}m";
            $array_res[] = $row;
        }
        return $array_res;
    }

    public function getById($id)
    {
        $db = Database::getInstance();
        $id = (int)$id;
        $query = "SELECT vb.*, v.vehicle_no, v.ref_no AS vehicle_ref_no,
                  TIMESTAMPDIFF(MINUTE, vb.breakdown_date, COALESCE(vb.resolved_date, NOW())) as downtime_minutes
                  FROM vehicle_breakdowns vb 
                  INNER JOIN vehicles v ON vb.vehicle_id = v.id 
                  WHERE vb.id={$id} LIMIT 1";
        
        $result = mysqli_fetch_array($db->readQuery($query));
        if ($result) {
            $mins = $result['downtime_minutes'];
            $days = floor($mins / 1440);
            $hours = floor(($mins % 1440) / 60);
            $rmins = $mins % 60;
            $result['downtime_formatted'] = ($days > 0 ? "{$days}d " : "") . "{$hours}h {$rmins}m";
        }
        return $result ?: null;
    }
}
