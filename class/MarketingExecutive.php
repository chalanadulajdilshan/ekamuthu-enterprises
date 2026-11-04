<?php

class MarketingExecutive
{
    public $id;
    public $code;
    public $full_name;
    public $nic;
    public $mobile_number;
    public $whatsapp_number;
    public $target_month;
    public $target;
    public $joined_date;
    public $is_active;
    public $remark;
    public $created_at;

    public $queue;

    // Constructor to initialize the object with an ID (fetch data from the DB)
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `marketing_executive` WHERE `id` = " . (int)$id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->code = $result['code'];
                $this->full_name = $result['full_name'];
                $this->nic = $result['nic'];
                $this->mobile_number = $result['mobile_number'];
                $this->whatsapp_number = $result['whatsapp_number'];
                $this->target_month = $result['target_month'];
                $this->target = $result['target'];
                $this->joined_date = $result['joined_date'];
                $this->is_active = $result['is_active'];
                $this->remark = $result['remark'];
                $this->created_at = $result['created_at'];
                $this->queue = $result['queue'];

            }
        }
    }

    // Create a new marketing executive
    public function create()
    {
        $query = "INSERT INTO `marketing_executive` 
            (`code`, `full_name`, `nic`, `mobile_number`, `whatsapp_number`, `target_month`, `target`, `joined_date`, `is_active`, `remark`, `created_at`) 
            VALUES 
            (
                '$this->code', '$this->full_name', '$this->nic', '$this->mobile_number', '$this->whatsapp_number',
                '$this->target_month', '$this->target', '$this->joined_date', '$this->is_active', '$this->remark', NOW()
            )";

        $db = Database::getInstance();
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON);
        } else {
            return false;
        }
    }

    // Update an existing marketing executive
    public function update()
    {
        $query = "UPDATE `marketing_executive` SET 
            `code` = '$this->code',
            `full_name` = '$this->full_name',
            `nic` = '$this->nic',
            `mobile_number` = '$this->mobile_number',
            `whatsapp_number` = '$this->whatsapp_number',
            `target_month` = '$this->target_month',
            `target` = '$this->target',
            `joined_date` = '$this->joined_date',
            `is_active` = '$this->is_active',
            `remark` = '$this->remark'
            WHERE `id` = '$this->id'";

        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Delete a marketing executive
    public function delete()
    {
        $query = "DELETE FROM `marketing_executive` WHERE `id` = '$this->id'";
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Get all marketing executives
    public function all()
    {
        $query = "SELECT * FROM `marketing_executive` ORDER BY `full_name` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getActiveExecutives()
    {
        $query = "SELECT * FROM `marketing_executive` where is_active = 1 ORDER BY `queue` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);

        $array_res = array();
        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    
    public function getLastID()
    {
        $query = "SELECT * FROM `marketing_executive` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'];
    }

    // Rep app login. Blank password keeps the current one. Returns an error message or null.
    public function setAppLogin($id, $username, $password, $enabled)
    {
        RepFollowup::ensureSchema();
        $db = Database::getInstance();
        $id = (int)$id;
        $username = trim($username);

        if ($username === '') {
            $db->readQuery("UPDATE `marketing_executive` SET `app_username` = NULL, `app_enabled` = 0 WHERE `id` = $id");
            return null;
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            return 'App username must be 3-50 letters/numbers (no spaces)';
        }

        $usernameSafe = $db->escapeString($username);
        $taken = $db->readQuery("SELECT id FROM `marketing_executive` WHERE `app_username` = '$usernameSafe' AND `id` != $id");
        if (mysqli_num_rows($taken) > 0) {
            return 'App username is already used by another rep';
        }

        $current = mysqli_fetch_assoc($db->readQuery("SELECT `app_password` FROM `marketing_executive` WHERE `id` = $id"));
        $set = "`app_username` = '$usernameSafe', `app_enabled` = " . ($enabled ? 1 : 0);
        if ($password !== '') {
            if (strlen($password) < 6) {
                return 'App password must be at least 6 characters';
            }
            $set .= ", `app_password` = '" . $db->escapeString(password_hash($password, PASSWORD_DEFAULT)) . "'";
            // New password logs the rep out of the app everywhere
            $db->readQuery("DELETE FROM `rep_app_tokens` WHERE `rep_id` = $id");
        } elseif (empty($current['app_password'])) {
            return 'Please set an app password';
        }

        $db->readQuery("UPDATE `marketing_executive` SET $set WHERE `id` = $id");
        return null;
    }

    // Returns the rep row on success, false otherwise
    public static function appLogin($username, $password)
    {
        RepFollowup::ensureSchema();
        $db = Database::getInstance();
        $usernameSafe = $db->escapeString(trim($username));
        $rep = mysqli_fetch_assoc($db->readQuery("SELECT * FROM `marketing_executive` WHERE `app_username` = '$usernameSafe' AND `app_enabled` = 1 AND `is_active` = 1 LIMIT 1"));

        if (!$rep || empty($rep['app_password']) || !password_verify($password, $rep['app_password'])) {
            return false;
        }
        $db->readQuery("UPDATE `marketing_executive` SET `app_last_login` = NOW() WHERE `id` = " . (int)$rep['id']);
        return $rep;
    }


}
?>
