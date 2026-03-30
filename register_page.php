<?php
include 'class/include.php';

$db = Database::getInstance();

// Insert page
$page_name = "Vehicle Repair Report";
$page_url = "vehicle-repair-report.php";
$page_category = 4; // Reports
$sub_page_category = 5; // Others
$page_icon = "uil-truck";

$check_query = "SELECT id FROM pages WHERE page_url = '$page_url'";
$check_result = $db->readQuery($check_query);

if (mysqli_num_rows($check_result) == 0) {
    $insert_query = "INSERT INTO pages (page_name, page_url, page_category, sub_page_category, page_icon) VALUES ('$page_name', '$page_url', $page_category, $sub_page_category, '$page_icon')";
    $db->readQuery($insert_query);
    $page_id = mysqli_insert_id($db->DB_CON);
    echo "Page created with ID: $page_id\n";
} else {
    $row = mysqli_fetch_assoc($check_result);
    $page_id = $row['id'];
    echo "Page already exists with ID: $page_id\n";
}

// Give permission to the current user (if logged in) or admin
if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $perm_check = "SELECT id FROM user_permission WHERE user_id = $user_id AND page_id = $page_id";
    $perm_check_res = $db->readQuery($perm_check);
    if (mysqli_num_rows($perm_check_res) == 0) {
        $perm_query = "INSERT INTO user_permission (user_id, page_id, add_page, edit_page, search_page, delete_page, print_page, other_page) VALUES ($user_id, $page_id, 1, 1, 1, 1, 1, 1)";
        $db->readQuery($perm_query);
    } else {
        $db->readQuery("UPDATE user_permission SET add_page=1, edit_page=1, search_page=1, delete_page=1, print_page=1, other_page=1 WHERE user_id = $user_id AND page_id = $page_id");
    }
    echo "Permission added/updated for current user ($user_id)\n";
}

// Also give to all admins (user type 1)
$admin_query = "SELECT id FROM users WHERE type = 1";
$admin_result = $db->readQuery($admin_query);
while ($admin = mysqli_fetch_assoc($admin_result)) {
    $admin_id = $admin['id'];
    $perm_check = "SELECT id FROM user_permission WHERE user_id = $admin_id AND page_id = $page_id";
    $perm_check_res = $db->readQuery($perm_check);
    if (mysqli_num_rows($perm_check_res) == 0) {
        $perm_query = "INSERT INTO user_permission (user_id, page_id, add_page, edit_page, search_page, delete_page, print_page, other_page) VALUES ($admin_id, $page_id, 1, 1, 1, 1, 1, 1)";
        $db->readQuery($perm_query);
    } else {
        $db->readQuery("UPDATE user_permission SET add_page=1, edit_page=1, search_page=1, delete_page=1, print_page=1, other_page=1 WHERE user_id = $admin_id AND page_id = $page_id");
    }
}
echo "Permissions added/updated for all admins\n";
?>
