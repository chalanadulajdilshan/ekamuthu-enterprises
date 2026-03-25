<?php

class IssueNote
{
    public $id;
    public $issue_note_code;
    public $rent_invoice_id;
    public $customer_id;
    public $issue_date;
    public $issue_status;
    public $remarks;
    public $department_id;
    public $image_path;
    public $created_at;
    public $updated_at;

    public function __construct($id = null)
    {
        if ($id) {
            $query = "SELECT * FROM `issue_notes` WHERE `id` = " . (int) $id;
            $db = Database::getInstance();
            $result = mysqli_fetch_array($db->readQuery($query));

            if ($result) {
                $this->id = $result['id'];
                $this->issue_note_code = $result['issue_note_code'];
                $this->rent_invoice_id = $result['rent_invoice_id'];
                $this->customer_id = $result['customer_id'];
                $this->issue_date = $result['issue_date'];
                $this->issue_status = $result['issue_status'];
                $this->remarks = $result['remarks'];
                $this->department_id = $result['department_id'];
                $this->image_path = $result['image_path'];
                $this->created_at = $result['created_at'];
                $this->updated_at = $result['updated_at'];
            }
        }
    }

    public function create()
    {
        $db = Database::getInstance();
        $deptId = ($this->department_id !== null && $this->department_id !== '') ? (int)$this->department_id : 0;

        $query = "INSERT INTO `issue_notes` (
            `issue_note_code`, `rent_invoice_id`, `customer_id`, `issue_date`, `issue_status`, `remarks`, `department_id`, `image_path`
        ) VALUES (
            '" . $db->escapeString($this->issue_note_code) . "',
            '" . (int) $this->rent_invoice_id . "',
            '" . (int) $this->customer_id . "',
            '" . $db->escapeString($this->issue_date) . "',
            '" . $db->escapeString($this->issue_status) . "',
            '" . $db->escapeString($this->remarks) . "',
            '" . $deptId . "',
            '" . $db->escapeString($this->image_path) . "'
        )";

        $result = $db->readQuery($query);

        if ($result) {
            $this->id = mysqli_insert_id($db->DB_CON);
            return $this->id;
        }
        return false;
    }

    public function update()
    {
        $db = Database::getInstance();
        $deptId = ($this->department_id !== null && $this->department_id !== '') ? (int)$this->department_id : 0;

        $query = "UPDATE `issue_notes` SET
            `rent_invoice_id` = '" . (int) $this->rent_invoice_id . "',
            `customer_id` = '" . (int) $this->customer_id . "',
            `issue_date` = '" . $db->escapeString($this->issue_date) . "',
            `issue_status` = '" . $db->escapeString($this->issue_status) . "',
            `remarks` = '" . $db->escapeString($this->remarks) . "',
            `department_id` = '" . $deptId . "',
            `image_path` = '" . $db->escapeString($this->image_path) . "'
            WHERE `id` = " . (int) $this->id;

        return $db->readQuery($query) ? true : false;
    }

    public function delete()
    {
        $rentInvoiceId = $this->rent_invoice_id;
        
        $ITEM = new IssueNoteItem(null);
        $ITEM->deleteByIssueNoteId($this->id);

        $query = "DELETE FROM `issue_notes` WHERE `id` = " . (int) $this->id;
        $db = Database::getInstance();
        $res = $db->readQuery($query);
        
        if ($res && $rentInvoiceId) {
            self::syncRentInvoiceStats($rentInvoiceId);
        }
        
        return $res;
    }

    public static function syncRentInvoiceStats($rentInvoiceId)
    {
        $db = Database::getInstance();
        $rentInvoiceId = (int)$rentInvoiceId;

        // 1. Reset all rent items to bill_qty/billed amounts first (as a baseline)
        $resetSql = "UPDATE equipment_rent_items 
                     SET quantity = bill_qty, 
                         amount = total_rent_amount / GREATEST(bill_qty, 1) * bill_qty,
                         deposit_amount = deposit_amount / GREATEST(bill_qty, 1) * bill_qty,
                         pending_qty = bill_qty - COALESCE(total_returned_qty, 0)
                     WHERE rent_id = $rentInvoiceId AND sub_equipment_id IS NULL";
        // Note: The above calculation is tricky because we don't know the exact per-unit rates if they weren't stored separately.
        // However, for PS Ekamuthu, typically standard rates apply.
        // A safer way is to fetch the rates from a valid state or just recalculate based on existing valid notes.

        // Better approach: Recalculate based on ALL non-cancelled issue notes
        $validNotesSql = "SELECT n.id FROM issue_notes n WHERE n.rent_invoice_id = $rentInvoiceId AND n.issue_status != 'cancelled'";
        $validNotesRes = $db->readQuery($validNotesSql);
        $validNoteIds = [];
        while($row = mysqli_fetch_assoc($validNotesRes)) {
            $validNoteIds[] = (int)$row['id'];
        }

        // Pre-calc total issued qty across all valid notes (used for status calculation)
        $overallIssued = 0;
        if (!empty($validNoteIds)) {
            $idsStr = implode(',', $validNoteIds);
            $overallIssuedSql = "SELECT COALESCE(SUM(issued_quantity), 0) AS total FROM issue_note_items WHERE issue_note_id IN ($idsStr)";
            $overallIssuedRow = mysqli_fetch_assoc($db->readQuery($overallIssuedSql));
            $overallIssued = (float)($overallIssuedRow['total'] ?? 0);
        }

        // Get billed items as baseline
        $billedItemsSql = "SELECT * FROM equipment_rent_items WHERE rent_id = $rentInvoiceId";
        $billedItemsRes = $db->readQuery($billedItemsSql);
        
        while ($bItem = mysqli_fetch_assoc($billedItemsRes)) {
            $eqId = (int)$bItem['equipment_id'];
            $subEqId = $bItem['sub_equipment_id'] ? (int)$bItem['sub_equipment_id'] : null;
            $billQty = (float)$bItem['bill_qty'];
            $deptId = !empty($bItem['department_id']) ? (int)$bItem['department_id'] : null;
            
            // Calculate total issued from valid notes
            $issuedTotal = 0;
            $rentItemId = (int)$bItem['id'];
            if (!empty($validNoteIds)) {
                $idsStr = implode(',', $validNoteIds);
                $fallbackCond = "equipment_id = $eqId AND " . ($subEqId ? "sub_equipment_id = $subEqId" : "sub_equipment_id IS NULL") . " AND " . ($deptId ? "department_id = $deptId" : "department_id IS NULL");
                $issuedSql = "SELECT SUM(issued_quantity) as total 
                              FROM issue_note_items 
                              WHERE issue_note_id IN ($idsStr) 
                              AND (rent_item_id = $rentItemId OR (rent_item_id IS NULL AND $fallbackCond))";
                $issuedRes = mysqli_fetch_assoc($db->readQuery($issuedSql));
                $issuedTotal = (float)($issuedRes['total'] ?? 0);
            }

            // If nothing was issued for this line, keep existing billed values (avoid resetting to 0)
            if ($issuedTotal <= 0) {
                continue;
            }

            // Update stats for items without sub-equipment (inventory items)
            if (empty($subEqId)) {
                $newPending = max(0, $issuedTotal - (float)$bItem['total_returned_qty']);

                // Sync sub_equipment rented_qty (restock previous delta)
                $previousQty = (float)$bItem['quantity'];
                $deptId = (int)$bItem['department_id'];
                $qtyDiff = $issuedTotal - $previousQty;
                if ($qtyDiff !== 0 && !empty($deptId)) {
                    $restockSql = "UPDATE sub_equipment 
                                   SET rented_qty = GREATEST(0, rented_qty + ($qtyDiff)) 
                                   WHERE equipment_id = $eqId AND department_id = $deptId";
                    $db->readQuery($restockSql);
                }

                // Preserve billed amounts/deposits; only sync issued quantities & pending qty
                $updateSql = "UPDATE equipment_rent_items 
                              SET quantity = $issuedTotal, 
                                  pending_qty = $newPending 
                              WHERE id = " . (int)$bItem['id'];
                $db->readQuery($updateSql);
            }
        }

        // 2. Update Invoice Issuing Status
        $overallOrderedSql = "SELECT SUM(bill_qty) as total FROM equipment_rent_items WHERE rent_id = $rentInvoiceId";
        $totOrd = (float)mysqli_fetch_assoc($db->readQuery($overallOrderedSql))['total'];

        // Use actual issued quantities from issue_note_items (not the recalculated item quantity) to avoid false zeros
        $totIss = $overallIssued;
        
        $newDocStatus = 0; // Not Issued
        if ($totIss > 0) {
            if ($totIss >= $totOrd) {
                $newDocStatus = 2; // Fully Issued
            } else {
                $newDocStatus = 1; // Partially Issued
            }
        }
        $db->readQuery("UPDATE equipment_rent SET issue_status = $newDocStatus WHERE id = $rentInvoiceId");
    }

    public function getItems()
    {
        $ITEM = new IssueNoteItem(null);
        return $ITEM->getByIssueNoteId($this->id);
    }

    public function getLastID()
    {
        $query = "SELECT * FROM `issue_notes` ORDER BY `id` DESC LIMIT 1";
        $db = Database::getInstance();
        $result = mysqli_fetch_array($db->readQuery($query));
        return $result['id'] ?? 0;
    }

    public function fetchForDataTable($request)
    {
        $db = Database::getInstance();

        $start = isset($request['start']) ? (int) $request['start'] : 0;
        $length = isset($request['length']) ? (int) $request['length'] : 100;
        $search = $request['search']['value'] ?? '';

        // Total records
        $totalSql = "SELECT COUNT(*) as total FROM issue_notes";
        $totalQuery = $db->readQuery($totalSql);
        $totalData = mysqli_fetch_assoc($totalQuery)['total'];

        // Search filter
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (i.issue_note_code LIKE '%$search%' OR er.bill_number LIKE '%$search%' OR cm.name LIKE '%$search%')";
        }

        $excludeReturned = isset($request['exclude_returned']) && $request['exclude_returned'] == 'true';

        // Base query with totals
        $baseQuery = "SELECT i.*, er.bill_number, cm.name as customer_name, cm.code as customer_code,
                      (SELECT SUM(issued_quantity) FROM issue_note_items WHERE issue_note_id = i.id) as total_issued,
                       (SELECT SUM(iri.return_quantity) FROM issue_return_items iri 
                       INNER JOIN issue_returns ir ON iri.return_id = ir.id 
                       WHERE ir.issue_note_id = i.id) as total_returned,
                       dm.name as department_name
                       FROM issue_notes i
                       LEFT JOIN equipment_rent er ON i.rent_invoice_id = er.id
                       LEFT JOIN customer_master cm ON i.customer_id = cm.id
                       LEFT JOIN department_master dm ON i.department_id = dm.id
                      $where";

        if ($excludeReturned) {
            $baseQuery .= " HAVING (total_issued > IFNULL(total_returned, 0))";
        }

        // Filtered records count
        $filteredSql = "SELECT COUNT(*) as filtered FROM ($baseQuery) as sub";
        $filteredQuery = $db->readQuery($filteredSql);
        $filteredData = mysqli_fetch_assoc($filteredQuery)['filtered'];

        // Paginated query
        $sql = "$baseQuery ORDER BY i.id DESC LIMIT $start, $length";
        $dataQuery = $db->readQuery($sql);

        $data = [];
        $key = 1;

        $statusLabels = [
            'pending' => '<span class="badge bg-soft-secondary font-size-12">Pending</span>',
            'issued' => '<span class="badge bg-soft-success font-size-12">Issued</span>',
            'cancelled' => '<span class="badge bg-soft-danger font-size-12">Cancelled</span>'
        ];

        while ($row = mysqli_fetch_assoc($dataQuery)) {
            $statusLabel = $statusLabels[$row['issue_status']] ?? $row['issue_status'];

            $nestedData = [
                "key" => $key,
                "id" => $row['id'],
                "issue_note_code" => $row['issue_note_code'],
                "rent_invoice_ref" => $row['bill_number'],
                "customer_name" => $row['customer_code'] . ' - ' . $row['customer_name'],
                "department" => $row['department_name'] ?? '-',
                "issue_date" => $row['issue_date'],
                "status" => $statusLabel,
                "created_at" => $row['created_at']
            ];

            $data[] = $nestedData;
            $key++;
        }

        return [
            "draw" => intval($request['draw'] ?? 1),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($filteredData),
            "data" => $data
        ];
    }
}
