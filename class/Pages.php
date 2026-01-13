<?php

class Pages
{
    public $id;
    public $page_category;
    public $sub_page_category;
    public $page_name;
    public $page_icon;
    public $page_url;

    private static $HAS_PAGE_ICON_COLUMN = null;

    private static function hasPageIconColumn()
    {
        if (self::$HAS_PAGE_ICON_COLUMN !== null) {
            return self::$HAS_PAGE_ICON_COLUMN;
        }

        $db = Database::getInstance();
        $result = $db->readQuery("SHOW COLUMNS FROM `pages` LIKE 'page_icon'");

        self::$HAS_PAGE_ICON_COLUMN = ($result && mysqli_num_rows($result) > 0);
        return self::$HAS_PAGE_ICON_COLUMN;
    }

    // Constructor to initialize the Page object with an ID (fetch data from the DB)
    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT  * FROM `pages` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->page_category = $result['page_category'];
                $this->sub_page_category = $result['sub_page_category'];
                $this->page_name = $result['page_name'];
                $this->page_icon = isset($result['page_icon']) ? $result['page_icon'] : null;
                $this->page_url = $result['page_url'];
            }
        }
    }

    // Create a new page record in the database
    public function create()
    {
        $db = Database::getInstance();

        $columns = "`page_category`,`sub_page_category`, `page_name`";
        $values = "'" . (int) $this->page_category . "',
             '" . $db->escapeString($this->sub_page_category) . "',
            '" . $db->escapeString($this->page_name) . "'";

        if (self::hasPageIconColumn()) {
            $columns .= ", `page_icon`";
            $values .= ",
            '" . $db->escapeString($this->page_icon) . "'";
        }

        $columns .= ", `page_url`";
        $values .= ",
            '" . $db->escapeString($this->page_url) . "'";

        $query = "INSERT INTO `pages` ($columns) VALUES (
            $values)";
        $result = $db->readQuery($query);

        if ($result) {
            return mysqli_insert_id($db->DB_CON); // Return the ID of the newly inserted record
        } else {
            return false; // Return false if the insertion fails
        }
    }

    // Update an existing page record
    public function update()
    {
        $db = Database::getInstance();

        $query = "UPDATE `pages` SET 
            `page_category` = '" . (int) $this->page_category . "',
            `sub_page_category` = '" . $db->escapeString($this->sub_page_category) . "',
            `page_name` = '" . $db->escapeString($this->page_name) . "'";

        if (self::hasPageIconColumn()) {
            $query .= ",
            `page_icon` = '" . $db->escapeString($this->page_icon) . "'";
        }

        $query .= ",
            `page_url` = '" . $db->escapeString($this->page_url) . "'
            WHERE `id` = " . (int) $this->id;
        $result = $db->readQuery($query);

        if ($result) {
            return true; // Return true if the update is successful
        } else {
            return false; // Return false if the update fails
        }
    }

    // Delete a page record by ID
    public function delete()
    {
        $query = "DELETE FROM `pages` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        return $db->readQuery($query);
    }

    // Retrieve all page records
    public function all()
    {
        $query = "SELECT * FROM `pages` ORDER BY `page_category` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    // Retrieve pages by category
    public function getPagesByCategory($category)
    {
        $query = "SELECT * FROM `pages` WHERE `page_category` = '" . $category . "' ORDER BY `queue` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }

    public function getPagesBySubCategory($sub_category)
    {
        $query = "SELECT * FROM `pages` WHERE `sub_page_category` = '" . $sub_category . "' ORDER BY `queue` ASC";
        $db = Database::getInstance();
        $result = $db->readQuery($query);
        $array_res = array();

        while ($row = mysqli_fetch_array($result)) {
            array_push($array_res, $row);
        }

        return $array_res;
    }


}
?>
