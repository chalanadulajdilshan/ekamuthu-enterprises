<?php

include '../../class/include.php';

header('Content-Type: application/json; charset=UTF8');

if ((isset($_GET['action']) && $_GET['action'] === 'download_item_import_template') || (isset($_POST['action']) && $_POST['action'] === 'download_item_import_template')) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="item-master-import-template.csv"');

    $sample = 0;
    if (isset($_GET['sample'])) {
        $sample = (int) $_GET['sample'];
    } elseif (isset($_POST['sample'])) {
        $sample = (int) $_POST['sample'];
    }

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'code',
        'name',
        'brand',
        'size',
        'pattern',
        'group',
        'category',
        'stock_type',
        'list_price',
        'discount',
        'invoice_price',
        're_order_level',
        're_order_qty',
        'note',
        'is_active'
    ]);

    if ($sample === 1) {
        $db = Database::getInstance();

        $brandId = 1;
        $brandName = 'BRAND';
        $res = mysqli_query($db->DB_CON, "SELECT id, name FROM brands WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $brandId = (int) $r['id'];
            $brandName = (string) $r['name'];
        }

        $groupId = 1;
        $res = mysqli_query($db->DB_CON, "SELECT id, name FROM group_master WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $groupId = (int) $r['id'];
        }

        $categoryId = 1;
        $res = mysqli_query($db->DB_CON, "SELECT id, name FROM category_master WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $categoryId = (int) $r['id'];
        }

        $stockTypeId = 1;
        $res = mysqli_query($db->DB_CON, "SELECT id, name FROM stock_type WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
        if ($res && ($r = mysqli_fetch_assoc($res))) {
            $stockTypeId = (int) $r['id'];
        }

        $sampleItemName = trim($brandName) . ' SAMPLE ITEM';
        fputcsv($out, [
            '',
            $sampleItemName,
            $brandId,
            '10',
            'PATTERN',
            $groupId,
            $categoryId,
            $stockTypeId,
            '1000',
            '0',
            '1000',
            '0',
            '1',
            'SAMPLE ROW - REPLACE WITH YOUR DATA',
            '1'
        ]);
    }

    fclose($out);
    exit;
}

function itemMasterNormalizeHeader($value)
{
    $value = (string) $value;
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = trim($value);
    $value = preg_replace('/\s+/', '_', $value);
    $value = str_replace(['-', '.', '/'], '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return strtolower($value);
}

function itemMasterColLettersToIndex($letters)
{
    $letters = strtoupper((string) $letters);
    $num = 0;
    $len = strlen($letters);
    for ($i = 0; $i < $len; $i++) {
        $num = $num * 26 + (ord($letters[$i]) - 64);
    }
    return $num - 1;
}

function itemMasterReadCsvRows($path, $maxRows = 5000)
{
    $rows = [];
    $handle = fopen($path, 'r');
    if (!$handle) {
        throw new Exception('Unable to read CSV file');
    }

    $firstLine = fgets($handle);
    if ($firstLine === false) {
        fclose($handle);
        return [];
    }
    rewind($handle);

    $comma = substr_count($firstLine, ',');
    $semi = substr_count($firstLine, ';');
    $tab = substr_count($firstLine, "\t");
    $delimiter = ',';
    if ($semi > $comma && $semi >= $tab) {
        $delimiter = ';';
    } elseif ($tab > $comma && $tab > $semi) {
        $delimiter = "\t";
    }

    while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (!is_array($data)) {
            continue;
        }

        $allEmpty = true;
        foreach ($data as $v) {
            if (trim((string) $v) !== '') {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            continue;
        }

        $rows[] = $data;
        if (count($rows) >= $maxRows) {
            break;
        }
    }
    fclose($handle);

    if (count($rows) <= 1) {
        $content = @file_get_contents($path);
        if ($content !== false) {
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $lines = preg_split('/\r\n|\n|\r/', $content);
            if (is_array($lines) && count($lines) > 1) {
                $fallback = [];
                foreach ($lines as $ln) {
                    if (trim((string) $ln) === '') {
                        continue;
                    }
                    $fallback[] = str_getcsv($ln, $delimiter);
                    if (count($fallback) >= $maxRows) {
                        break;
                    }
                }
                if (count($fallback) > count($rows)) {
                    $rows = $fallback;
                }
            }
        }
    }

    return $rows;
}

function itemMasterStripXmlNamespaces($xml)
{
    $xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xml);
    $xml = preg_replace('/<([a-zA-Z0-9]+):/', '<', $xml);
    $xml = preg_replace('/<\/([a-zA-Z0-9]+):/', '</', $xml);
    return $xml;
}

function itemMasterParseXlsxSheetRows($sheetXmlString, $sharedStrings, $maxRows = 5000)
{
    $sheetXmlString = itemMasterStripXmlNamespaces($sheetXmlString);
    $sheet = @simplexml_load_string($sheetXmlString);
    if (!$sheet) {
        return [];
    }

    if (!isset($sheet->sheetData)) {
        return [];
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        if (!isset($row->c)) {
            continue;
        }
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) {
                continue;
            }
            $colIndex = itemMasterColLettersToIndex($m[1]);

            $t = isset($c['t']) ? (string) $c['t'] : '';
            $val = '';

            if ($t === 'inlineStr' && isset($c->is)) {
                if (isset($c->is->t)) {
                    $val = (string) $c->is->t;
                } elseif (isset($c->is->r)) {
                    $acc = '';
                    foreach ($c->is->r as $run) {
                        $acc .= isset($run->t) ? (string) $run->t : '';
                    }
                    $val = $acc;
                }
            } elseif (isset($c->v)) {
                $val = (string) $c->v;
                if ($t === 's') {
                    $val = $sharedStrings[(int) $val] ?? '';
                }
            }
            $cells[$colIndex] = $val;
        }

        if (empty($cells)) {
            continue;
        }

        ksort($cells);
        $maxIndex = max(array_keys($cells));
        $rowArr = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $rowArr[] = $cells[$i] ?? '';
        }

        $hasData = false;
        foreach ($rowArr as $v) {
            if (trim((string) $v) !== '') {
                $hasData = true;
                break;
            }
        }
        if (!$hasData) {
            continue;
        }

        $rows[] = $rowArr;
        if (count($rows) >= $maxRows) {
            break;
        }
    }

    return $rows;
}

function itemMasterReadXlsxRows($path, $maxRows = 5000)
{
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive extension is required to read XLSX files');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new Exception('Unable to open XLSX file');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sharedXml = itemMasterStripXmlNamespaces($sharedXml);
        $sx = @simplexml_load_string($sharedXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $acc = '';
                    foreach ($si->r as $run) {
                        $acc .= isset($run->t) ? (string) $run->t : '';
                    }
                    $sharedStrings[] = $acc;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
    }

    $sheetNames = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (is_string($name) && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
            $sheetNames[] = $name;
        }
    }
    sort($sheetNames);

    if (empty($sheetNames)) {
        $zip->close();
        throw new Exception('No worksheet found in XLSX');
    }

    $bestRows = [];
    foreach ($sheetNames as $sheetName) {
        $sheetXml = $zip->getFromName($sheetName);
        if ($sheetXml === false) {
            continue;
        }
        $parsed = itemMasterParseXlsxSheetRows($sheetXml, $sharedStrings, $maxRows);
        if (count($parsed) > count($bestRows)) {
            $bestRows = $parsed;
        }
        if (count($bestRows) >= 2) {
            break;
        }
    }

    $zip->close();
    return $bestRows;
}

if (isset($_POST['action']) && $_POST['action'] === 'import_excel') {
    $result = [
        'status' => 'error',
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
        'message' => ''
    ];

    try {
        if (!isset($_FILES['excel_file'])) {
            throw new Exception('No file uploaded');
        }

        if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error');
        }

        $tmpPath = $_FILES['excel_file']['tmp_name'];
        $origName = $_FILES['excel_file']['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            throw new Exception('Only .xlsx or .csv files are allowed');
        }

        $rows = $ext === 'csv' ? itemMasterReadCsvRows($tmpPath) : itemMasterReadXlsxRows($tmpPath);
        $detectedRows = is_array($rows) ? count($rows) : 0;
        if (!is_array($rows) || $detectedRows < 2) {
            $fileSize = @filesize($tmpPath);
            $preview = @file_get_contents($tmpPath, false, null, 0, 10000);
            $lbCount = 0;
            if ($preview !== false) {
                $lbCount = preg_match_all('/\r\n|\n|\r/', $preview, $m);
            }

            throw new Exception(
                'File has ' . $detectedRows . ' row(s). ' .
                'Uploaded: ' . $origName . ' (' . $ext . '). ' .
                'Size: ' . ($fileSize !== false ? $fileSize : 'unknown') . ' bytes. ' .
                'Line breaks detected in first 10KB: ' . $lbCount . '. ' .
                'Please ensure the first row contains column headers and there is at least 1 item row. ' .
                'If you are using CSV, open it in Notepad and confirm there is a 2nd line (a new row under the headers).'
            );
        }

        $headerRow = array_shift($rows);
        $headerMap = [];
        foreach ($headerRow as $idx => $h) {
            $key = itemMasterNormalizeHeader($h);
            if ($key !== '' && !isset($headerMap[$key])) {
                $headerMap[$key] = (int) $idx;
            }
        }

        $aliases = [
            'code' => ['code', 'item_code', 'itemcode'],
            'name' => ['name', 'item_name', 'itemname'],
            'brand' => ['brand', 'brand_id', 'manufacturer_brand', 'manufacturerbrand'],
            'size' => ['size', 'item_size', 'itemsize'],
            'pattern' => ['pattern', 'item_pattern', 'itempattern'],
            'group' => ['group', 'group_id', 'item_group', 'itemgroup'],
            'category' => ['category', 'category_id', 'item_category', 'itemcategory'],
            'stock_type' => ['stock_type', 'stocktype', 'stock_type_id', 'stocktype_id'],
            'list_price' => ['list_price', 'listprice'],
            'discount' => ['discount', 'dis', 'dis_%', 'dis_percent', 'dispercentage'],
            'invoice_price' => ['invoice_price', 'invoiceprice', 'selling_price', 'sellingprice'],
            're_order_level' => ['re_order_level', 'reorder_level', 'reorderlevel'],
            're_order_qty' => ['re_order_qty', 'reorder_qty', 'reorderqty'],
            'note' => ['note', 'notes', 'item_note', 'itemnote'],
            'is_active' => ['is_active', 'active', 'status']
        ];

        $cols = [];
        foreach ($aliases as $canonical => $keys) {
            foreach ($keys as $k) {
                $nk = itemMasterNormalizeHeader($k);
                if (isset($headerMap[$nk])) {
                    $cols[$canonical] = $headerMap[$nk];
                    break;
                }
            }
        }

        if (!isset($cols['name'])) {
            throw new Exception('Missing required column: name');
        }
        if (!isset($cols['brand'])) {
            throw new Exception('Missing required column: brand');
        }
        if (!isset($cols['group'])) {
            throw new Exception('Missing required column: group');
        }
        if (!isset($cols['category'])) {
            throw new Exception('Missing required column: category');
        }
        if (!isset($cols['stock_type'])) {
            throw new Exception('Missing required column: stock_type');
        }
        if (!isset($cols['list_price'])) {
            throw new Exception('Missing required column: list_price');
        }

        $db = Database::getInstance();

        $resolveId = function ($table, $value) use ($db) {
            $value = trim((string) $value);
            if ($value === '') {
                return 0;
            }
            if (is_numeric($value)) {
                return (int) $value;
            }
            $esc = mysqli_real_escape_string($db->DB_CON, $value);
            $sql = "SELECT id FROM `" . $table . "` WHERE LOWER(name) = LOWER('" . $esc . "') LIMIT 1";
            $q = mysqli_query($db->DB_CON, $sql);
            if ($q && ($r = mysqli_fetch_assoc($q))) {
                return (int) $r['id'];
            }
            return 0;
        };

        $DOCUMENT_TRACKING = new DocumentTracking(null);

        $rowNumber = 1;
        foreach ($rows as $row) {
            $rowNumber++;

            $get = function ($key) use ($row, $cols) {
                if (!isset($cols[$key])) {
                    return '';
                }
                $idx = (int) $cols[$key];
                return isset($row[$idx]) ? $row[$idx] : '';
            };

            $code = trim((string) $get('code'));
            $name = trim((string) $get('name'));
            $brandRaw = $get('brand');
            $size = trim((string) $get('size'));
            $pattern = trim((string) $get('pattern'));
            $groupRaw = $get('group');
            $categoryRaw = $get('category');
            $stockTypeRaw = $get('stock_type');
            $listPriceRaw = $get('list_price');
            $discountRaw = $get('discount');
            $invoicePriceRaw = $get('invoice_price');
            $reOrderLevelRaw = $get('re_order_level');
            $reOrderQtyRaw = $get('re_order_qty');
            $note = trim((string) $get('note'));
            $isActiveRaw = $get('is_active');

            if ($name === '') {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Missing name'];
                continue;
            }

            $brandId = $resolveId('brands', $brandRaw);
            $groupId = $resolveId('group_master', $groupRaw);
            $categoryId = $resolveId('category_master', $categoryRaw);
            $stockTypeId = $resolveId('stock_type', $stockTypeRaw);

            if ($brandId <= 0) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Invalid brand: ' . trim((string) $brandRaw)];
                continue;
            }
            if ($groupId <= 0) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Invalid group: ' . trim((string) $groupRaw)];
                continue;
            }
            if ($categoryId <= 0) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Invalid category: ' . trim((string) $categoryRaw)];
                continue;
            }
            if ($stockTypeId <= 0) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Invalid stock_type: ' . trim((string) $stockTypeRaw)];
                continue;
            }

            $listPrice = (float) str_replace(',', '', (string) $listPriceRaw);
            $discount = (float) str_replace(',', '', (string) $discountRaw);
            $invoicePrice = (float) str_replace(',', '', (string) $invoicePriceRaw);
            $reOrderLevel = (float) str_replace(',', '', (string) $reOrderLevelRaw);
            $reOrderQty = (float) str_replace(',', '', (string) $reOrderQtyRaw);

            if ($listPrice <= 0) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'Invalid list_price'];
                continue;
            }

            if ($invoicePrice <= 0 && $listPrice > 0) {
                $invoicePrice = $listPrice * (1 - ($discount / 100));
            }
            if ($discount <= 0 && $invoicePrice > 0 && $listPrice > 0) {
                $discount = (($listPrice - $invoicePrice) / $listPrice) * 100;
            }
            if ($invoicePrice < 0) {
                $invoicePrice = 0;
            }

            $isActive = 1;
            if ($isActiveRaw !== '' && $isActiveRaw !== null) {
                $s = strtolower(trim((string) $isActiveRaw));
                $isActive = in_array($s, ['1', 'true', 'yes', 'active'], true) ? 1 : 0;
            }

            $existingId = 0;
            $existingCode = '';
            if ($code !== '') {
                $escCode = mysqli_real_escape_string($db->DB_CON, $code);
                $q = mysqli_query($db->DB_CON, "SELECT id, code FROM item_master WHERE code = '" . $escCode . "' LIMIT 1");
                if ($q && ($r = mysqli_fetch_assoc($q))) {
                    $existingId = (int) $r['id'];
                    $existingCode = (string) $r['code'];
                }
            }

            if ($existingId <= 0) {
                $escName = mysqli_real_escape_string($db->DB_CON, $name);
                $q = mysqli_query($db->DB_CON, "SELECT id, code FROM item_master WHERE name = '" . $escName . "' LIMIT 1");
                if ($q && ($r = mysqli_fetch_assoc($q))) {
                    $existingId = (int) $r['id'];
                    $existingCode = (string) $r['code'];
                }
            }

            if ($existingId <= 0) {
                if ($code === '') {
                    $newId = $DOCUMENT_TRACKING->incrementDocumentId('item');
                    if (!$newId) {
                        $result['skipped']++;
                        $result['errors'][] = ['row' => $rowNumber, 'message' => 'Unable to generate item code'];
                        continue;
                    }
                    $code = 'TI/0' . $newId;
                }

                $sql = "INSERT INTO item_master (`code`,`name`,`brand`,`size`,`pattern`,`group`,`category`,`re_order_level`,`re_order_qty`,`stock_type`,`note`,`list_price`,`invoice_price`,`discount`,`is_active`) VALUES (";
                $sql .= "'" . mysqli_real_escape_string($db->DB_CON, $code) . "',";
                $sql .= "'" . mysqli_real_escape_string($db->DB_CON, $name) . "',";
                $sql .= "'" . (int) $brandId . "',";
                $sql .= "'" . mysqli_real_escape_string($db->DB_CON, $size) . "',";
                $sql .= "'" . mysqli_real_escape_string($db->DB_CON, $pattern) . "',";
                $sql .= "'" . (int) $groupId . "',";
                $sql .= "'" . (int) $categoryId . "',";
                $sql .= "'" . (float) $reOrderLevel . "',";
                $sql .= "'" . (float) $reOrderQty . "',";
                $sql .= "'" . (int) $stockTypeId . "',";
                $sql .= "'" . mysqli_real_escape_string($db->DB_CON, $note) . "',";
                $sql .= "'" . (float) $listPrice . "',";
                $sql .= "'" . (float) $invoicePrice . "',";
                $sql .= "'" . (float) $discount . "',";
                $sql .= "'" . (int) $isActive . "')";

                $ok = mysqli_query($db->DB_CON, $sql);
                if (!$ok) {
                    $result['skipped']++;
                    $result['errors'][] = ['row' => $rowNumber, 'message' => 'DB insert failed'];
                    continue;
                }

                $result['inserted']++;
                continue;
            }

            $finalCode = $code !== '' ? $code : $existingCode;
            $sql = "UPDATE item_master SET ";
            $sql .= "`code`='" . mysqli_real_escape_string($db->DB_CON, $finalCode) . "',";
            $sql .= "`name`='" . mysqli_real_escape_string($db->DB_CON, $name) . "',";
            $sql .= "`brand`='" . (int) $brandId . "',";
            $sql .= "`size`='" . mysqli_real_escape_string($db->DB_CON, $size) . "',";
            $sql .= "`pattern`='" . mysqli_real_escape_string($db->DB_CON, $pattern) . "',";
            $sql .= "`group`='" . (int) $groupId . "',";
            $sql .= "`category`='" . (int) $categoryId . "',";
            $sql .= "`re_order_level`='" . (float) $reOrderLevel . "',";
            $sql .= "`re_order_qty`='" . (float) $reOrderQty . "',";
            $sql .= "`stock_type`='" . (int) $stockTypeId . "',";
            $sql .= "`note`='" . mysqli_real_escape_string($db->DB_CON, $note) . "',";
            $sql .= "`list_price`='" . (float) $listPrice . "',";
            $sql .= "`invoice_price`='" . (float) $invoicePrice . "',";
            $sql .= "`discount`='" . (float) $discount . "',";
            $sql .= "`is_active`='" . (int) $isActive . "'";
            $sql .= " WHERE id = '" . (int) $existingId . "'";

            $ok = mysqli_query($db->DB_CON, $sql);
            if (!$ok) {
                $result['skipped']++;
                $result['errors'][] = ['row' => $rowNumber, 'message' => 'DB update failed'];
                continue;
            }
            $result['updated']++;
        }

        if (isset($_SESSION['id'])) {
            $AUDIT_LOG = new AuditLog(null);
            $AUDIT_LOG->ref_id = 0;
            $AUDIT_LOG->ref_code = 'ITEM_MASTER_IMPORT';
            $AUDIT_LOG->action = 'IMPORT';
            $AUDIT_LOG->description = 'IMPORT ITEMS | inserted: ' . $result['inserted'] . ' | updated: ' . $result['updated'] . ' | skipped: ' . $result['skipped'];
            $AUDIT_LOG->user_id = $_SESSION['id'];
            $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
            $AUDIT_LOG->create();
        }

        $result['status'] = 'success';
        $result['message'] = 'Import completed';
        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['message'] = $e->getMessage();
        echo json_encode($result);
        exit;
    }
}

// Fetch single item by id (for reliable prefill)
if (isset($_POST['action']) && $_POST['action'] === 'get_by_id') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $response = ['status' => 'error', 'message' => 'Item not found'];
    try {
        if ($id <= 0) {
            throw new Exception('Invalid id');
        }
        $db = Database::getInstance();
        $sql = "SELECT * FROM item_master WHERE id = $id LIMIT 1";
        $res = $db->readQuery($sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $item = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'brand_id' => (int)$row['brand'],
                'category_id' => (int)$row['category'],
                'group' => (int)$row['group'],
                'size' => $row['size'],
                'pattern' => $row['pattern'],
                'list_price' => (float)$row['list_price'],
                'invoice_price' => (float)$row['invoice_price'],
                're_order_level' => $row['re_order_level'],
                're_order_qty' => $row['re_order_qty'],
                'stock_type' => $row['stock_type'],
                'discount' => $row['discount'],
                'note' => $row['note'],
                'status' => (int)$row['is_active']
            ];
            echo json_encode(['status' => 'success', 'item' => $item]);
            exit;
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

// Fetch single item by exact code (for reliable prefill)
if (isset($_POST['action']) && $_POST['action'] === 'get_by_code') {
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    $response = ['status' => 'error', 'message' => 'Item not found'];
    try {
        if ($code === '') {
            throw new Exception('Invalid code');
        }
        $db = Database::getInstance();
        $escCode = mysqli_real_escape_string($db->DB_CON, $code);
        $sql = "SELECT * FROM item_master WHERE code = '" . $escCode . "' LIMIT 1";
        $res = $db->readQuery($sql);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $item = [
                'id' => (int)$row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'brand_id' => (int)$row['brand'],
                'category_id' => (int)$row['category'],
                'group' => (int)$row['group'],
                'size' => $row['size'],
                'pattern' => $row['pattern'],
                'list_price' => (float)$row['list_price'],
                'invoice_price' => (float)$row['invoice_price'],
                're_order_level' => $row['re_order_level'],
                're_order_qty' => $row['re_order_qty'],
                'stock_type' => $row['stock_type'],
                'discount' => $row['discount'],
                'note' => $row['note'],
                'status' => (int)$row['is_active']
            ];
            echo json_encode(['status' => 'success', 'item' => $item]);
            exit;
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

// Create a new item
if (isset($_POST['create'])) {

    $ITEM = new ItemMaster(NULL); // Create a new ItemMaster object

    // Set item details
    $ITEM->code = $_POST['code'];
    $ITEM->name = $_POST['name'];

    // Check for duplicate item name
    if (ItemMaster::isNameDuplicate($_POST['name'])) {
        $result = [
            "status" => 'error',
            "message" => 'Item name already exists'
        ];
        echo json_encode($result);
        exit();
    }

    $ITEM->brand = $_POST['brand'];
    $ITEM->size = $_POST['size'];
    $ITEM->pattern = $_POST['pattern'];
    $ITEM->group = $_POST['group'];
    $ITEM->category = $_POST['category'];
    $ITEM->list_price = $_POST['list_price'];
    $ITEM->invoice_price = $_POST['invoice_price'];
    $ITEM->re_order_level = $_POST['re_order_level'];
    $ITEM->re_order_qty = $_POST['re_order_qty'];
    $ITEM->stock_type = $_POST['stock_type'];
    $ITEM->note = $_POST['note'];
    $ITEM->discount = $_POST['discount'];
    $ITEM->is_active = isset($_POST['is_active']) ? 1 : 0; //  

    // Attempt to create the item
    $res = $ITEM->create();


    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $res;
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'CREATE';
    $AUDIT_LOG->description = 'CREATE ITEM NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    $DOCUMENT_TRACKING = new DocumentTracking(null);
    $DOCUMENT_TRACKING->incrementDocumentId('item');


    if ($res) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

// Update item details
if (isset($_POST['update'])) {

    $ITEM = new ItemMaster($_POST['item_id']); // Retrieve item by ID

    // Update item details
    $ITEM->code = $_POST['code'];
    $ITEM->name = $_POST['name'];

    // Check for duplicate item name
    if (ItemMaster::isNameDuplicate($_POST['name'], $_POST['item_id'])) {
        $result = [
            "status" => 'error',
            "message" => 'Item name already exists'
        ];
        echo json_encode($result);
        exit();
    }

    $ITEM->brand = $_POST['brand'];
    $ITEM->size = $_POST['size'];
    $ITEM->pattern = $_POST['pattern'];
    $ITEM->group = $_POST['group'];
    $ITEM->category = $_POST['category'];
    $ITEM->re_order_level = $_POST['re_order_level'];
    $ITEM->re_order_qty = $_POST['re_order_qty'];
    $ITEM->stock_type = $_POST['stock_type'];
    $ITEM->note = $_POST['note'];
    $ITEM->list_price = $_POST['list_price'];
    $ITEM->invoice_price = $_POST['invoice_price'];
    $ITEM->discount = $_POST['discount'];
    $ITEM->is_active = isset($_POST['is_active']) ? 1 : 0;

    // Attempt to update the item
    $result = $ITEM->update();


    //audit log
    $AUDIT_LOG = new AuditLog(NUll);
    $AUDIT_LOG->ref_id = $_POST['item_id'];
    $AUDIT_LOG->ref_code = $_POST['code'];
    $AUDIT_LOG->action = 'UPDATE';
    $AUDIT_LOG->description = 'UPDATE ITEM NO #' . $_POST['code'];
    $AUDIT_LOG->user_id = $_SESSION['id'];
    $AUDIT_LOG->created_at = date("Y-m-d H:i:s");
    $AUDIT_LOG->create();

    if ($result) {
        $result = [
            "status" => 'success'
        ];
        echo json_encode($result);
        exit();
    } else {
        $result = [
            "status" => 'error'
        ];
        echo json_encode($result);
        exit();
    }
}

// Delete item
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $ITEM_MASTER = new ItemMaster($_POST['id']);

        if (!$ITEM_MASTER->id) {
            throw new Exception('Item not found');
        }

        $result = $ITEM_MASTER->delete();

        if ($result) {
            // Add audit log
            $AUDIT_LOG = new AuditLog(null);
            $AUDIT_LOG->ref_id = $_POST['id'];
            $AUDIT_LOG->ref_code = $ITEM_MASTER->code;
            $AUDIT_LOG->action = 'DELETE';
            $AUDIT_LOG->description = 'DELETED ITEM #' . $ITEM_MASTER->code;
            $AUDIT_LOG->user_id = $_SESSION['id'];
            $AUDIT_LOG->created_at = date('Y-m-d H:i:s');
            $AUDIT_LOG->create();

            echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully']);
        } else {
            throw new Exception('Failed to delete item');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

//filter for item for invoices
if (isset($_POST['filter_by_invoice'])) {
    $ITEM_MASTER = new ItemMaster();
    $response = $ITEM_MASTER->fetchForDataTable($_REQUEST);

    echo json_encode($response);
    exit;
}

if (isset($_POST['filter'])) {


    $ITEM_MASTER = new ItemMaster();
    $response = $ITEM_MASTER->fetchForDataTable($_REQUEST);

    echo json_encode($response);
    exit;
}

// Handle DataTable server-side processing
if (isset($_POST['action']) && $_POST['action'] === 'fetch_for_datatable') {
    $itemMaster = new ItemMaster();

    // If department_id is provided, ensure it's an integer
    if (isset($_POST['department_id']) && !empty($_POST['department_id'])) {
        $_POST['department_id'] = (int)$_POST['department_id'];
    } else {
        // If no department is selected, you might want to handle this case
        // For now, we'll unset it to show all items
        unset($_POST['department_id']);
    }

    $result = $itemMaster->fetchForDataTable($_POST);
    echo json_encode($result);
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'get_items_with_stock') {
    $itemMaster = new ItemMaster();
    $items = $itemMaster::getItemsWithStock();
    echo json_encode(['data' => $items]);
    exit();
}

// Handle stock adjustment item filtering
if (isset($_POST['action']) && $_POST['action'] === 'fetch_for_stock_adjustment') {
    $response = [
        'draw' => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => null
    ];

    try {
        if (!isset($_POST['department_id']) || empty($_POST['department_id'])) {
            throw new Exception('Department ID is required');
        }

        $department_id = (int)$_POST['department_id'];
        $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';
        $show_zero_qty = isset($_POST['show_zero_qty']) ? (bool)$_POST['show_zero_qty'] : false;

        // Get items with department stock
        $items = ItemMaster::getItemsByDepartmentAndStock(
            $department_id,
            $show_zero_qty ? -1 : 1, // 1 means filter out zero quantity items, -1 means show all
            $search
        );

        // Ensure $items is an array
        if (!is_array($items)) {
            throw new Exception('Invalid data format received from getItemsByDepartmentAndStock');
        }

        // Format the response for DataTables
        $formattedData = [];
        foreach ($items as $item) {
            $formattedData[] = [
                'DT_RowId' => 'row_' . $item['id'],
                'id' => $item['id'],
                'code' => $item['code'],
                'name' => $item['name'],
                'brand' => $item['brand_name'] ?? '',
                'category' => $item['category_name'] ?? '',
                'list_price' => number_format($item['list_price'], 2),
                'invoice_price' => number_format($item['invoice_price'], 2),
                'available_qty' => (int)($item['available_qty'] ?? 0),
                'discount' => isset($item['discount']) ? $item['discount'] . '%' : '0%',
                'status_label' => ($item['is_active'] ?? 0) == 1 ?
                    '<span class="badge bg-success">Active</span>' :
                    '<span class="badge bg-danger">Inactive</span>',
                'department_stock' => [
                    [
                        'department_id' => $department_id,
                        'quantity' => (int)($item['available_qty'] ?? 0)
                    ]
                ]
            ];
        }

        $response['recordsTotal'] = count($formattedData);
        $response['recordsFiltered'] = count($formattedData);
        $response['data'] = $formattedData;
    } catch (Exception $e) {
        error_log('Error in fetch_for_stock_adjustment: ' . $e->getMessage());
        $response['error'] = $e->getMessage();
    }

    // Ensure we're sending valid JSON
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Get total cost, total invoice, and profit % from all ARN lots
if (isset($_POST['action']) && $_POST['action'] === 'get_totals') {
    try {
        $db = Database::getInstance();
        $sql = "SELECT 
            SUM(cost * qty) as total_cost, 
            SUM(invoice_price * qty) as total_invoice 
            FROM stock_item_tmp";
        $departmentId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        if ($departmentId > 0) {
            $sql .= " WHERE department_id = $departmentId";
        }
        $res = $db->readQuery($sql);
        $totals = ['total_cost' => 0, 'total_invoice' => 0, 'profit_percentage' => 0];
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $totals['total_cost'] = (float)$row['total_cost'];
            $totals['total_invoice'] = (float)$row['total_invoice'];
            if ($totals['total_cost'] > 0) {
                $totals['profit_percentage'] = (($totals['total_invoice'] - $totals['total_cost']) / $totals['total_cost']) * 100;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $totals]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch ARN-wise stock lots for a given item id (for Live Stock row details)
if (isset($_POST['action']) && $_POST['action'] === 'get_stock_tmp_by_item') {
    try {
        $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $departmentFilterId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
        if ($itemId <= 0) {
            throw new Exception('Invalid item_id');
        }

        $STOCK_TMP = new StockItemTmp(NULL);
        $lots = $STOCK_TMP->getByItemId($itemId);

        // Skip lots with zero or negative quantity
        $lots = array_values(array_filter($lots, function ($l) {
            return isset($l['qty']) && (float)$l['qty'] > 0;
        }));

        // If a department is specified, filter to that department only
        if ($departmentFilterId > 0) {
            $lots = array_values(array_filter($lots, function ($l) use ($departmentFilterId) {
                return isset($l['department_id']) && (int)$l['department_id'] === $departmentFilterId;
            }));
        }

        // Decorate with ARN number and department name
        $decorated = [];
        foreach ($lots as $lot) {
            $arnNo = null;
            if (!empty($lot['arn_id'])) {
                $ARN = new ArnMaster((int)$lot['arn_id']);
                if ($ARN && isset($ARN->arn_no)) {
                    $arnNo = $ARN->arn_no;
                }
            }
            $deptName = null;
            if (!empty($lot['department_id'])) {
                $DEPT = new DepartmentMaster((int)$lot['department_id']);
                if ($DEPT && isset($DEPT->name)) {
                    $deptName = $DEPT->name;
                }
            }
            $lot['arn_no'] = $arnNo;
            $lot['department'] = $deptName;
            $decorated[] = $lot;
        }
        echo json_encode(['status' => 'success', 'data' => $decorated]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Export stock data for Excel/CSV /PDF using ItemMaster class methods
if (isset($_POST['action']) && $_POST['action'] === 'export_stock') {
    try {
        // Get filter parameters
        $department_id = isset($_POST['department_id']) ? $_POST['department_id'] : 'all';
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $stock_only = isset($_POST['stock_only']) ? (int)$_POST['stock_only'] : 1;

        $items = [];

        if ($department_id !== 'all' && $department_id !== '' && $department_id !== null) {
            // Use ItemMaster class method for specific department
            $dept_id = (int)$department_id;
            $items = ItemMaster::getItemsByDepartmentAndStock($dept_id, 1, '');
        } else {
            // Use ItemMaster class method for all items with stock
            $all_items = ItemMaster::getItemsWithStock();

            // Filter for active items only
            foreach ($all_items as $item) {
                if ($item['is_active'] == $status) {
                    $items[] = $item;
                }
            }
        }

        // Transform data for export format
        $export_data = [];
        foreach ($items as $item) {
            $category = new CategoryMaster($item['category']);
            $export_item = [
                'id' => $item['id'],
                'code' => $item['code'] ?: '',
                'name' => $item['name'] ?: '',
                'category' => isset($category->name) ? $category->name : '',
                'list_price' => (float)($item['list_price'] ?: 0),
                'discount' => (float)($item['discount'] ?: 0),
                'invoice_price' => (float)($item['invoice_price'] ?: 0),
                'quantity' => (float)($item['total_qty'] ?: 0),
                'stock_status' => 'In Stock',
                'arn_lots' => []
            ];

            // Calculate dealer price if not using invoice_price
            if ($export_item['invoice_price'] <= 0 && $export_item['list_price'] && $export_item['discount']) {
                $export_item['invoice_price'] = (float)$export_item['list_price'] * (1 - (float)$export_item['discount'] / 100);
            }

            // Determine stock status
            $reorder_level = (float)($item['re_order_level'] ?: 0);
            if ($export_item['quantity'] <= 0) {
                $export_item['stock_status'] = 'Out of Stock';
            } elseif ($export_item['quantity'] <= $reorder_level) {
                $export_item['stock_status'] = 'Re-order';
            }

            // Get ARN lots for this item using StockItemTmp class
            $stockTmp = new StockItemTmp();
            $item_id = (int)$item['id'];

            if ($department_id !== 'all' && $department_id !== '' && $department_id !== null) {
                // Get ARN lots for specific department
                $lots = $stockTmp->getByItemIdAndDepartment($item_id, (int)$department_id);
            } else {
                // Get all ARN lots for the item
                $lots = $stockTmp->getByItemId($item_id);
            }

            foreach ($lots as $lot) {
                // Skip zero or negative quantity lots
                if ((float)$lot['qty'] <= 0) {
                    continue;
                }

                // Get ARN details
                $arn = new ArnMaster($lot['arn_id']);

                $export_item['arn_lots'][] = [
                    'arn_no' => $arn->arn_no ?? '',
                    'cost' => (float)$lot['cost'],
                    'qty' => (float)$lot['qty'],
                    'list_price' => (float)$lot['list_price'],
                    'invoice_price' => (float)$lot['invoice_price']
                ];
            }

            $export_data[] = $export_item;
        }

        // Return data for export
        echo json_encode([
            'status' => 'success',
            'data' => $export_data,
            'export_type' => 'stock_export'
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}
