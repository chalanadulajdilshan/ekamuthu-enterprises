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
            query += ` AND im.category = ?`;
            params.push(category);
        }

        if (!all) {
            query += ` HAVING available_qty > 0`;
        }

        query += ` ORDER BY im.name ASC`;

        const [rows] = await db.query(query, params);
        return rows;
    }

    static async create(data) {
        const {
            code, name, brand, category, list_price, invoice_price,
            discount, re_order_level, re_order_qty, is_active,
            barcode, reminder_note, note, image_file
        } = data;

        const [result] = await db.query(
            `INSERT INTO item_master (
                code, name, brand, reminder_note, barcode, image_file, category,
                re_order_level, re_order_qty, stock_type, note,
                list_price, invoice_price, discount, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)`,
            [
                code, name, brand || 1, reminder_note || '', barcode || '',
                image_file || '',
                category || 1, re_order_level || 0, re_order_qty || 0,
                note || '', list_price || 0, invoice_price || 0,
                discount || 0, is_active ? 1 : 0
            ]
        );

        return result;
    }

    static async update(id, data) {
        const {
            code, name, brand, category, list_price, invoice_price,
            discount, re_order_level, re_order_qty, is_active,
            barcode, reminder_note, note, image_file
        } = data;

        let updateQuery = `UPDATE item_master SET
                code = ?, name = ?, brand = ?, category = ?,
                re_order_level = ?, re_order_qty = ?,
                list_price = ?, invoice_price = ?, discount = ?, is_active = ?,
                barcode = ?, reminder_note = ?, note = ?`;

        const params = [
            code, name, brand || 1, category || 1,
            re_order_level || 0, re_order_qty || 0,
            list_price || 0, invoice_price || 0, discount || 0, is_active ? 1 : 0,
            barcode || '', reminder_note || '', note || ''
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
}

module.exports = Product;
