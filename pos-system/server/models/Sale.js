const db = require('../config/db');

class Sale {
    static async create(data) {
        const connection = await db.getConnection();
        try {
            await connection.beginTransaction();

            const {
                customer_id, customer_name, customer_mobile, customer_address,
                department_id, payment_type, items, sub_total, discount,
                tax, grand_total, remark
            } = data;

            // Generate invoice number: POS-YYYYMMDD-XXXX
            const today = new Date();
            const dateStr = today.toISOString().slice(0, 10).replace(/-/g, '');

            const [lastInvoice] = await connection.query(
                `SELECT invoice_no FROM sales_invoice
                 WHERE invoice_no LIKE ?
                 ORDER BY id DESC LIMIT 1`,
                [`POS-${dateStr}-%`]
            );

            let sequence = 1;
            if (lastInvoice.length > 0) {
                const lastNum = parseInt(lastInvoice[0].invoice_no.split('-').pop());
                sequence = lastNum + 1;
            }

            const invoice_no = `POS-${dateStr}-${String(sequence).padStart(4, '0')}`;
            const invoice_date = today.toISOString().slice(0, 19).replace('T', ' ');

            // Calculate final cost from items
            let final_cost = 0;
            for (const item of items) {
                final_cost += (item.cost || 0) * item.quantity;
            }

            // Insert sales_invoice
            const [invoiceResult] = await connection.query(
                `INSERT INTO sales_invoice (
                    ref_id, invoice_type, invoice_no, invoice_date, company_id,
                    customer_id, customer_name, customer_mobile, customer_address,
                    recommended_person, department_id, sale_type, discount_type,
                    final_cost, payment_type, sub_total, discount, tax, grand_total,
                    outstanding_settle_amount, remark, credit_period, due_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    '', 'POS', invoice_no, invoice_date, 1,
                    customer_id || 0, customer_name || 'Walk-in Customer',
                    customer_mobile || '', customer_address || '',
                    '', department_id || 1, payment_type == 2 ? 2 : 1, 1,
                    final_cost, payment_type || 1, sub_total, discount || 0,
                    tax || 0, grand_total, 0, remark || 'POS Sale', 0, null
                ]
            );

            const invoiceId = invoiceResult.insertId;

            // Insert sales_invoice_items & update stock
            for (const item of items) {
                await connection.query(
                    `INSERT INTO sales_invoice_items (
                        invoice_id, item_code, item_name, service_item_code,
                        quantity, cost, price, discount
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                    [
                        invoiceId, item.code, item.name, '',
                        item.quantity, item.cost || 0, item.price, item.discount || 0
                    ]
                );

                await connection.query(
                    `UPDATE stock_master
                     SET quantity = quantity - ?
                     WHERE item_id = ? AND quantity > 0
                     ORDER BY id ASC LIMIT 1`,
                    [item.quantity, item.item_id]
                );
            }

            await connection.commit();

            return { invoice_id: invoiceId, invoice_no, grand_total };
        } catch (error) {
            await connection.rollback();
            throw error;
        } finally {
            connection.release();
        }
    }

    static async getRecent() {
        const [rows] = await db.query(`
            SELECT si.*,
                   dm.name as department_name
            FROM sales_invoice si
            LEFT JOIN department_master dm ON si.department_id = dm.id
            WHERE si.invoice_no LIKE 'POS-%'
            ORDER BY si.id DESC
            LIMIT 20
        `);
        return rows;
    }

    static async getById(id) {
        const [invoice] = await db.query('SELECT * FROM sales_invoice WHERE id = ?', [id]);
        if (!invoice.length) return null;

        const [items] = await db.query('SELECT * FROM sales_invoice_items WHERE invoice_id = ?', [id]);
        const [company] = await db.query('SELECT * FROM company_profile WHERE id = 1');

        return {
            invoice: invoice[0],
            items,
            company: company[0] || {}
        };
    }
}

module.exports = Sale;
