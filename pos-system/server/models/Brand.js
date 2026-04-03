const db = require('../config/db');

class Brand {
    static async getAll() {
        const [rows] = await db.query('SELECT * FROM brand ORDER BY name ASC');
        return rows;
    }

    static async getCategories() {
        const [rows] = await db.query('SELECT * FROM brand_category ORDER BY name ASC');
        return rows;
    }

    static async create(data) {
        const { category_id, name, country_id, is_active, remark } = data;
        const [result] = await db.query(
            `INSERT INTO brand (category_id, name, country_id, is_active, remark) VALUES (?, ?, ?, ?, ?)`,
            [category_id, name, country_id || '', is_active ? 1 : 0, remark || '']
        );
        return result;
    }

    static async update(id, data) {
        const { category_id, name, country_id, is_active, remark } = data;
        const [result] = await db.query(
            `UPDATE brand SET category_id = ?, name = ?, country_id = ?, is_active = ?, remark = ? WHERE id = ?`,
            [category_id, name, country_id || '', is_active ? 1 : 0, remark || '', id]
        );
        return result;
    }
}

module.exports = Brand;
