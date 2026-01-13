<?php
include '../../class/include.php';
header('Content-Type: application/json; charset=UTF8');

function formatMobileNumber($number)
{
    // Remove all non-numeric characters
    $number = preg_replace('/[^0-9]/', '', $number);

    // If number starts with 94, ensure it's 12 digits (94 + 10 digits)
    if (strpos($number, '94') === 0 && strlen($number) === 12) {
        return $number;
    }

    // If number starts with 0, remove it and add 94
    if (strpos($number, '0') === 0 && strlen($number) === 10) {
        return '94' . substr($number, 1);
    }

    // If number is 9 digits, assume it's missing the 94 prefix
    if (strlen($number) === 9) {
        return '94' . $number;
    }

    // Return as is if it doesn't match expected formats
    return $number;
}

if (isset($_POST['create']) || isset($_POST['update'])) {
    $isUpdate = isset($_POST['update']);
    $COMPANY = $isUpdate ? new CompanyProfile($_POST['company_id'] ?? 0) : new CompanyProfile();

    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/company-logos/';
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp'];
        $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedExtensions)) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $randomFileName = uniqid('logo_', true) . '.' . $fileExt;
            $uploadPath = $uploadDir . $randomFileName;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                // Delete old logo if exists and we're updating
                if ($isUpdate && !empty($COMPANY->image_name) && file_exists($uploadDir . $COMPANY->image_name)) {
                    @unlink($uploadDir . $COMPANY->image_name);
                }
                $COMPANY->image_name = $randomFileName;
            }
        }
    }

    // Handle favicon upload
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/company-logos/';
        $allowedExtensions = ['ico', 'png', 'jpg', 'jpeg'];
        $fileExt = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedExtensions)) {
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $randomFileName = uniqid('favicon_', true) . '.' . $fileExt;
            $uploadPath = $uploadDir . $randomFileName;

            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadPath)) {
                // Delete old favicon if exists and we're updating
                if ($isUpdate && !empty($COMPANY->favicon) && file_exists($uploadDir . $COMPANY->favicon)) {
                    @unlink($uploadDir . $COMPANY->favicon);
                }
                $COMPANY->favicon = $randomFileName;
            }
        }
    }

    // Set other company properties
    $COMPANY->name = $_POST['name'] ?? '';
    $COMPANY->address = $_POST['address'] ?? '';
    $COMPANY->email = $_POST['email'] ?? '';
    $COMPANY->mobile_number_1 = !empty($_POST['mobile_number_1']) ? formatMobileNumber($_POST['mobile_number_1']) : '';
    $COMPANY->mobile_number_2 = !empty($_POST['mobile_number_2']) ? formatMobileNumber($_POST['mobile_number_2']) : '';
    $COMPANY->mobile_number_3 = !empty($_POST['mobile_number_3']) ? formatMobileNumber($_POST['mobile_number_3']) : '';
    $COMPANY->vat_number = $_POST['vat_number'] ?? '';
    $COMPANY->is_active = isset($_POST['is_active']) ? 1 : 0;
    $COMPANY->is_vat = isset($_POST['is_vat']) ? 1 : 0;
    $COMPANY->is_one_company = isset($_POST['is_one_company']) ? 1 : 0;
    $COMPANY->is_credit = isset($_POST['is_credit']) ? 1 : 0;
    $COMPANY->vat_percentage = isset($_POST['vat_percentage']) ? (float)$_POST['vat_percentage'] : 0;
    $COMPANY->company_code = $_POST['company_code'] ?? '';
    $COMPANY->customer_id = $_POST['customer_id'] ?? null;
    $COMPANY->theme = $_POST['theme'] ?? '#3b5de7';

    $homeViewMode = $_POST['home_view_mode'] ?? 'both';
    $homeViewMode = in_array($homeViewMode, ['header', 'nav_buttons', 'both'], true) ? $homeViewMode : 'both';
    $COMPANY->home_view_mode = $homeViewMode;
    

    // Save to database
    $result = $isUpdate ? $COMPANY->update() : $COMPANY->create();

    if ($result) {
        $response = [
            'status' => 'success',
            'message' => $isUpdate ? 'Company profile updated successfully.' : 'Company profile created successfully.'
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Failed to save company profile.'
        ];
    }

    echo json_encode($response);
    exit();
}

if (isset($_POST['delete'])) {

    $COMPANY = new CompanyProfile($_POST['id']);

    $result = $COMPANY->delete();

    if ($result) {
        $response['status'] = 'success';
    } else {
        $response['status'] = 'error';
    }
    echo json_encode($response);
    exit();
}

// Handle GET request to fetch company data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $company = new CompanyProfile(1); // Assuming company ID is 1, adjust if needed

    if ($company) {
        $response = [
            'status' => 'success',
            'data' => [
                'id' => $company->id,
                'company_code' => $company->company_code,
                'name' => $company->name,
                'address' => $company->address,
                'mobile_number_1' => $company->mobile_number_1,
                'mobile_number_2' => $company->mobile_number_2,
                'mobile_number_3' => $company->mobile_number_3,
                'email' => $company->email,
                'image_name' => $company->image_name,
                'favicon' => $company->favicon,
                'is_active' => $company->is_active,
                'is_vat' => $company->is_vat,
                'vat_number' => $company->vat_number,
                'vat_percentage' => $company->vat_percentage,
                'is_one_company' => $company->is_one_company,
                'is_credit' => $company->is_credit,
                'customer_id' => $company->customer_id,
                'theme' => $company->theme ?? 'default',
                'home_view_mode' => $company->home_view_mode ?? 'both'
            ]
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Company profile not found.'
        ];
    }

    echo json_encode($response);
    exit();
}
