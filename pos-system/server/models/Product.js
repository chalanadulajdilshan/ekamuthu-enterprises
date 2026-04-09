const db = require('../config/db');

class Product {
    static async getAll({ search = '', category = '', all = false }) {
        let query = `
            SELECT
                im.id, im.code, im.name, im.brand, im.category,
                im.list_price, im.invoice_price, im.discount,
                im.is_active, im.barcode, im.reminder_note, im.re_order_level, im.re_order_qty, im.note,
                im.image_file,
                IFNULL(sm_total.total_qty, 0) as available_qty,
                IFNULL(latest_cost.cost_price, 0) as cost_price,
                cm.name as category_name,
                b.name as brand_name
            FROM item_master im
            LEFT JOIN (
                SELECT item_id, SUM(qty_remaining) as total_qty
                FROM item_batches
                GROUP BY item_id
            ) sm_total ON im.id = sm_total.item_id
            LEFT JOIN (
                SELECT ib1.item_id, ib1.cost_price
                FROM item_batches ib1
                INNER JOIN (
                    SELECT item_id, MAX(id) as max_id
                    FROM item_batches
                    GROUP BY item_id
                ) ib2 ON ib1.id = ib2.max_id
            ) latest_cost ON im.id = latest_cost.item_id
            LEFT JOIN category_master cm ON im.category = cm.id
            LEFT JOIN brands b ON im.brand = b.id
            WHERE 1=1
        `;

        const params = [];

        if (!all) {
            query += ` AND im.is_active = 1`;
        }

        if (search) {
            query += ` AND (im.name LIKE ? OR im.code LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`);
        }

        if (category) {
            query += ` AND im.department_id = ?`;
            params.push(category);
        }

        if (!all) {
            query += ` HAVING available_qty > 0`;
        }

        query += ` ORDER BY im.name ASC`;

        const [rows] = await db.query(query, params);
        return rows;
    }

    static async checkCodeDuplicate(code, excludeId = null) {
        let query = 'SELECT id FROM item_master WHERE code = ?';
        const params = [code];
        if (excludeId) {
            query += ' AND id != ?';
            params.push(excludeId);
        }
        const [rows] = await db.query(query, params);
        return rows.length > 0;
    }

    static async create(data) {
        const {
            barcode, size, note, image_file
        } = data;

        // Check for duplicate code
        if (await Product.checkCodeDuplicate(code)) {
            throw new Error(`Product code "${code}" is already in use.`);
        }

        const [result] = await db.query(
            `INSERT INTO item_master (
                code, name, brand, reminder_note, barcode, image_file, department_id,
                re_order_level, re_order_qty, max_stock, stock_type, note,
                list_price, net_price, tax_type, invoice_price, discount, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)`,
            [
                code, name, brand || 1, size || '', barcode || '',
                image_file || '',
                category || 1, re_order_level || 0, re_order_qty || 0, max_stock || 0,
                note || '', list_price || 0, net_price || 0, tax_type || 'T0-0',
                invoice_price || 0,
                discount || 0, is_active ? 1 : 0
            ]
        );

        return result;
    }

    static async update(id, data) {
        const {
            barcode, size, note, image_file
        } = data;

        // Check for duplicate code
        if (await Product.checkCodeDuplicate(code, id)) {
            throw new Error(`Product code "${code}" is already in use by another product.`);
        }

        let updateQuery = `UPDATE item_master SET
                code = ?, name = ?, brand = ?, department_id = ?,
                re_order_level = ?, re_order_qty = ?, max_stock = ?,
                list_price = ?, net_price = ?, tax_type = ?, invoice_price = ?, discount = ?,
                is_active = ?, barcode = ?, reminder_note = ?, note = ?`;

        const params = [
            code, name, brand || 1, category || 1,
            re_order_level || 0, re_order_qty || 0, max_stock || 0,
            list_price || 0, net_price || 0, tax_type || 'T0-0', invoice_price || 0, discount || 0,
            is_active ? 1 : 0,
            barcode || '', size || '', note || ''
        ];

        if (image_file) {
            updateQuery += `, image_file = ?`;
            params.push(image_file);
        }

        updateQuery += ` WHERE id = ?`;
        params.push(id);

        const [result] = await db.query(updateQuery, params);
        return result;
    }

    static async delete(id) {
        const [result] = await db.query('DELETE FROM item_master WHERE id = ?', [id]);
        return result;
    }
}

module.exports = Product;
