const db = require('../config/db');

class Grn {
    static async getAll() {
        const query = `
            SELECT gm.*, s.name as supplier_name, dm.name as department_name
            FROM grn_master gm
            LEFT JOIN supplier_master s ON gm.supplier_id = s.id
            LEFT JOIN department_master dm ON gm.department_id = dm.id
            ORDER BY gm.id DESC
            LIMIT 50
        `;
        const [rows] = await db.query(query);
        return rows;
    }

    static async getNextGrnNo() {
        const [rows] = await db.query('SELECT grn_no FROM grn_master ORDER BY id DESC LIMIT 1');
        if (rows.length === 0) return 'GRN/001';
        
        const lastNo = rows[0].grn_no;
        const matches = lastNo.match(/GRN\/(\d+)/);
        if (matches) {
            const nextNum = parseInt(matches[1]) + 1;
            return `GRN/${String(nextNum).padStart(3, '0')}`;
        }
        return 'GRN/001';
    }

    static async create(data) {
        const connection = await db.getConnection();
        try {
            await connection.beginTransaction();

            const {
                arn_no, supplier_id, department_id, entry_date, invoice_no, invoice_date,
                payment_type, items, sub_total, total_discount, total_arn_value, remark
            } = data;

            // 1. Insert into grn_master (Using yeni tablo isimleri)
            const [grnResult] = await connection.query(
                `INSERT INTO grn_master (
                    grn_no, supplier_id, department_id, entry_date, invoice_no, invoice_date,
                    payment_type, sub_total, total_discount, total_amount, 
                    remark, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Approved', NOW())`,
                [
                    arn_no, supplier_id, department_id, entry_date, invoice_no, invoice_date,
                    payment_type, sub_total, total_discount, total_arn_value, remark
                ]
            );

            const grnId = grnResult.insertId;

            // 2. Insert into grn_items and Update Stock via item_batches (FIFO)
            for (const item of items) {
                // a. Insert individual items
                await connection.query(
                    `INSERT INTO grn_items (
                        grn_id, item_id, item_code, qty,
                        discount_1, discount_2, discount_3, discount_4, discount_5,
                        list_price, actual_cost, selling_price, unit_total, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`,
                    [
                        grnId, item.item_id, item.item_code, item.quantity,
                        item.discount_1 || 0, item.discount_2 || 0, item.discount_3 || 0, 
                        item.discount_4 || 0, item.discount_5 || 0,
                        item.list_price, item.actual_cost, item.invoice_price, item.unit_total
                    ]
                );

                // b. Create a new BATCH for FIFO
                await connection.query(
                    `INSERT INTO item_batches (
                        item_id, grn_id, qty_received, qty_remaining, 
                        cost_price, selling_price, batch_no, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())`,
                    [
                        item.item_id, grnId, item.quantity, item.quantity, 
                        item.actual_cost, item.invoice_price, arn_no
                    ]
                );

                // c. Update latest prices in item_master
                await connection.query(
                    `UPDATE item_master SET list_price = ?, invoice_price = ? WHERE id = ?`,
                    [item.list_price, item.invoice_price, item.item_id]
                );
            }

            await connection.commit();
            return { id: grnId, grn_no: arn_no };
        } catch (error) {
            await connection.rollback();
            throw error;
        } finally {
            connection.release();
        }
    }

    static async getById(id) {
        const query = `
            SELECT gm.*, s.name as supplier_name, dm.name as department_name, s.code as supplier_code
            FROM grn_master gm
            LEFT JOIN supplier_master s ON gm.supplier_id = s.id
            LEFT JOIN department_master dm ON gm.department_id = dm.id
            WHERE gm.id = ?
        `;
        const [grnRows] = await db.query(query, [id]);
        
        if (grnRows.length === 0) return null;

        const [items] = await db.query('SELECT * FROM grn_items WHERE grn_id = ?', [id]);
        const [company] = await db.query('SELECT * FROM company_profile WHERE id = 1');

        return {
            grn: grnRows[0],
            items,
            company: company[0] || {}
        };
    }
}

module.exports = Grn;
