const db = require('../config/db');

class Grn {
    static async getAll() {
        const query = `
            SELECT am.*, s.name as supplier_name, dm.name as department_name
            FROM arn_master am
            LEFT JOIN brands s ON am.supplier_id = s.id
            LEFT JOIN department_master dm ON am.department = dm.id
            ORDER BY am.id DESC
            LIMIT 50
        `;
        const [rows] = await db.query(query);
        return rows;
    }

    static async getNextArnNo() {
        const [rows] = await db.query('SELECT arn_no FROM arn_master ORDER BY id DESC LIMIT 1');
        if (rows.length === 0) return 'ARN/001';
        
        const lastNo = rows[0].arn_no;
        const matches = lastNo.match(/ARN\/(\d+)/);
        if (matches) {
            const nextNum = parseInt(matches[1]) + 1;
            return `ARN/${String(nextNum).padStart(3, '0')}`;
        }
        return 'ARN/001';
    }

    static async create(data) {
        const connection = await db.getConnection();
        try {
            await connection.beginTransaction();

            const {
                arn_no, supplier_id, department_id, entry_date, invoice_no, invoice_date,
                payment_type, items, sub_total, total_discount, total_arn_value, remark
            } = data;

            // 1. Insert into arn_master
            const [arnResult] = await connection.query(
                `INSERT INTO arn_master (
                    arn_no, supplier_id, department, entry_date, bl_no, invoice_date,
                    purchase_type, sub_arn_value, total_discount, total_arn_value, 
                    remark, arn_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', NOW())`,
                [
                    arn_no, supplier_id, department_id, entry_date, invoice_no, invoice_date,
                    payment_type, sub_total, total_discount, total_arn_value, remark
                ]
            );

            const arnId = arnResult.insertId;

            // 2. Insert into arn_items and Update Stock
            for (const item of items) {
                // a. Insert individual items
                await connection.query(
                    `INSERT INTO arn_items (
                        arn_id, item_code, order_qty, received_qty,
                        discount_1, discount_2, discount_3, discount_4, discount_5,
                        list_price, invoice_price, unit_total, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
                    [
                        arnId, item.item_id, item.quantity, item.quantity,
                        item.discount_1 || 0, item.discount_2 || 0, item.discount_3 || 0, 
                        item.discount_4 || 0, item.discount_5 || 0,
                        item.list_price, item.invoice_price, item.unit_total
                    ]
                );

                // b. Update stock_master - use REPLACE or INSERT ... ON DUPLICATE KEY UPDATE
                // Check if stock entry exists for this item and department
                const [stockCheck] = await connection.query(
                    `SELECT id, quantity FROM stock_master WHERE item_id = ? AND location_id = ?`,
                    [item.item_id, department_id]
                );

                if (stockCheck.length > 0) {
                    await connection.query(
                        `UPDATE stock_master SET quantity = quantity + ?, updated_at = NOW() WHERE id = ?`,
                        [item.quantity, stockCheck[0].id]
                    );
                } else {
                    await connection.query(
                        `INSERT INTO stock_master (item_id, quantity, location_id, updated_at) VALUES (?, ?, ?, NOW())`,
                        [item.item_id, item.quantity, department_id]
                    );
                }

                // c. Update latest prices in item_master
                await connection.query(
                    `UPDATE item_master SET list_price = ?, invoice_price = ? WHERE id = ?`,
                    [item.list_price, item.invoice_price, item.item_id]
                );
            }

            await connection.commit();
            return { id: arnId, arn_no };
        } catch (error) {
            await connection.rollback();
            throw error;
        } finally {
            connection.release();
        }
    }
}

module.exports = Grn;
