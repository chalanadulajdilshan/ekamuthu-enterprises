const express = require('express');
const router = express.Router();
const db = require('../config/db');

// GET /api/products - Fetch all active products with stock
router.get('/products', async (req, res) => {
    try {
        const search = req.query.search || '';
        const category = req.query.category || '';
        
        let query = `
            SELECT 
                im.id, im.code, im.name, im.brand, im.category, 
                im.list_price, im.invoice_price, im.discount,
                im.is_active,
                IFNULL(sm_total.total_qty, 0) as available_qty,
                cm.name as category_name,
                b.name as brand_name
            FROM item_master im
            LEFT JOIN (
                SELECT item_id, SUM(quantity) as total_qty 
                FROM stock_master 
                GROUP BY item_id
            ) sm_total ON im.id = sm_total.item_id
            LEFT JOIN category_master cm ON im.category = cm.id
            LEFT JOIN brands b ON im.brand = b.id
            WHERE im.is_active = 1
        `;
        
        const params = [];
        
        if (search) {
            query += ` AND (im.name LIKE ? OR im.code LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`);
        }
        
        if (category) {
            query += ` AND im.category = ?`;
            params.push(category);
        }
        
        query += ` HAVING available_qty > 0 ORDER BY im.name ASC`;
        
        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching products:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/categories - Fetch all categories
router.get('/categories', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM category_master ORDER BY name ASC');
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching categories:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/customers - Fetch customers
router.get('/customers', async (req, res) => {
    try {
        const search = req.query.search || '';
        let query = 'SELECT id, code, name, mobile, address, email FROM customer_master WHERE 1=1';
        const params = [];
        
        if (search) {
            query += ` AND (name LIKE ? OR code LIKE ? OR mobile LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        query += ' ORDER BY name ASC LIMIT 50';
        
        const [rows] = await db.query(query, params);
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching customers:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/company - Fetch company profile
router.get('/company', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM company_profile WHERE id = 1');
        res.json({ success: true, data: rows[0] || {} });
    } catch (error) {
        console.error('Error fetching company:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/departments - Fetch departments
router.get('/departments', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM department_master ORDER BY name ASC');
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching departments:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/payment-types - Fetch payment types
router.get('/payment-types', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM payment_type ORDER BY name ASC');
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching payment types:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// POST /api/sales - Create a new POS sale
router.post('/sales', async (req, res) => {
    const connection = await db.getConnection();
    try {
        await connection.beginTransaction();
        
        const {
            customer_id, customer_name, customer_mobile, customer_address,
            department_id, payment_type, items, sub_total, discount,
            tax, grand_total, remark
        } = req.body;
        
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
        
        // Insert sales_invoice_items
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
            
            // Update stock_master - reduce quantity
            await connection.query(
                `UPDATE stock_master 
                 SET quantity = quantity - ? 
                 WHERE item_id = ? AND quantity > 0 
                 ORDER BY id ASC LIMIT 1`,
                [item.quantity, item.item_id]
            );
        }
        
        await connection.commit();
        
        res.json({
            success: true,
            message: 'Sale completed successfully',
            data: {
                invoice_id: invoiceId,
                invoice_no: invoice_no,
                grand_total: grand_total
            }
        });
    } catch (error) {
        await connection.rollback();
        console.error('Error creating sale:', error);
        res.status(500).json({ success: false, message: error.message });
    } finally {
        connection.release();
    }
});

// GET /api/sales/recent - Get recent POS sales
router.get('/sales/recent', async (req, res) => {
    try {
        const [rows] = await db.query(`
            SELECT si.*, 
                   dm.name as department_name
            FROM sales_invoice si
            LEFT JOIN department_master dm ON si.department_id = dm.id
            WHERE si.invoice_no LIKE 'POS-%'
            ORDER BY si.id DESC
            LIMIT 20
        `);
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('Error fetching recent sales:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/sales/:id - Get sale details
router.get('/sales/:id', async (req, res) => {
    try {
        const [invoice] = await db.query('SELECT * FROM sales_invoice WHERE id = ?', [req.params.id]);
        if (!invoice.length) {
            return res.status(404).json({ success: false, message: 'Invoice not found' });
        }
        
        const [items] = await db.query('SELECT * FROM sales_invoice_items WHERE invoice_id = ?', [req.params.id]);
        const [company] = await db.query('SELECT * FROM company_profile WHERE id = 1');
        
        res.json({
            success: true,
            data: {
                invoice: invoice[0],
                items: items,
                company: company[0] || {}
            }
        });
    } catch (error) {
        console.error('Error fetching sale details:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

// GET /api/dashboard-stats - POS dashboard stats
router.get('/dashboard-stats', async (req, res) => {
    try {
        const today = new Date().toISOString().slice(0, 10);
        
        const [todaySales] = await db.query(`
            SELECT COUNT(*) as count, IFNULL(SUM(grand_total), 0) as total
            FROM sales_invoice 
            WHERE DATE(invoice_date) = ? AND invoice_no LIKE 'POS-%' AND is_cancel = 0
        `, [today]);
        
        const [totalProducts] = await db.query(`
            SELECT COUNT(*) as count FROM item_master WHERE is_active = 1
        `);
        
        const [lowStock] = await db.query(`
            SELECT COUNT(*) as count FROM (
                SELECT im.id, IFNULL(SUM(sm.quantity), 0) as qty, im.re_order_level
                FROM item_master im
                LEFT JOIN stock_master sm ON im.id = sm.item_id
                WHERE im.is_active = 1
                GROUP BY im.id
                HAVING qty <= im.re_order_level AND qty > 0
            ) low_items
        `);
        
        res.json({
            success: true,
            data: {
                today_sales_count: todaySales[0].count,
                today_sales_total: todaySales[0].total,
                total_products: totalProducts[0].count,
                low_stock_count: lowStock[0].count
            }
        });
    } catch (error) {
        console.error('Error fetching dashboard stats:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

module.exports = router;
