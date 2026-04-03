const db = require('../config/db');

class Company {
    static async getProfile() {
        const [rows] = await db.query('SELECT * FROM company_profile WHERE id = 1');
        return rows[0] || {};
    }
}

module.exports = Company;
