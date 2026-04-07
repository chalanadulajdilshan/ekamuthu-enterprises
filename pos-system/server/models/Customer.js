const db = require('../config/db');

class Customer {
    static async getAll(search = '') {
        let query = 'SELECT id, code, name, mobile_number, address FROM customer_master WHERE 1=1';
        const params = [];

        if (search) {
            query += ` AND (name LIKE ? OR code LIKE ? OR mobile_number LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }

        query += ' ORDER BY name ASC LIMIT 50';

        const [rows] = await db.query(query, params);
        return rows;
    }
}

module.exports = Customer;
