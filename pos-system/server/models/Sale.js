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

            let totalFinalCost = 0;

            // 1. Insert sales_invoice header (final_cost will be updated after calculating from batches)
            const [invoiceResult] = await connection.query(
                `INSERT INTO sales_invoice (
                    invoice_no, invoice_date, customer_id, customer_name, customer_mobile, 
                    customer_address, department_id, payment_type, sub_total, 
                    discount, tax, grand_total, final_cost, status, remark
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
                [
                    invoice_no, invoice_date, customer_id || 0, 
                    customer_name || 'Walk-in Customer', customer_mobile || '', 
                    customer_address || '', department_id || 1, payment_type || 1, 
                    sub_total, discount || 0, tax || 0, grand_total, 
                    0, 1, remark || 'POS Sale'
                ]
            );

            const invoiceId = invoiceResult.insertId;

            // 2. Insert sales_invoice_items & update stock (FIFO)
            for (const item of items) {
                let remainingToDeduct = item.quantity;
                let totalItemCost = 0;

                // Validate sufficient stock before billing
                const [stockCheck] = await connection.query(
                    `SELECT SUM(qty_remaining) as total_stock FROM item_batches WHERE item_id = ?`,
                    [item.item_id]
                );
                const availableStock = stockCheck[0].total_stock || 0;
                
                if (availableStock < item.quantity) {
                    throw new Error(`Insufficient stock for item [${item.item_code || item.item_id}]. Available: ${availableStock}, Requested: ${item.quantity}`);
                }

                // Fetch batches in FIFO order
                const [batches] = await connection.query(
                    `SELECT id, qty_remaining, cost_price FROM item_batches WHERE item_id = ? AND qty_remaining > 0 ORDER BY created_at ASC`,
                    [item.item_id]
                );

                for (const batch of batches) {
                    if (remainingToDeduct <= 0) break;
                    const deduct = Math.min(remainingToDeduct, batch.qty_remaining);
                    
                    await connection.query(
                        `UPDATE item_batches SET qty_remaining = qty_remaining - ? WHERE id = ?`,
                        [deduct, batch.id]
                    );

                    totalItemCost += deduct * batch.cost_price;
                    remainingToDeduct -= deduct;
                }

                totalFinalCost += totalItemCost;
                const unitCost = item.quantity > 0 ? (totalItemCost / item.quantity) : 0;
                
                // Calculate item-level total: (price * qty) - discount
                const itemTotal = (item.price * item.quantity) - (item.discount || 0);

                await connection.query(
                    `INSERT INTO sales_invoice_items (
                        invoice_id, item_code, item_name,
                        quantity, cost, price, discount, total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
                    [
                        invoiceId, item.item_code || item.code, item.item_name || item.name,
                        item.quantity, unitCost, item.price, item.discount || 0, itemTotal
                    ]
                );
            }

            // 3. Update the header with the actual calculated final_cost
            await connection.query(
                `UPDATE sales_invoice SET final_cost = ? WHERE id = ?`,
                [totalFinalCost, invoiceId]
            );

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
