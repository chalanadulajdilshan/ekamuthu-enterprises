const db = require('../config/db');

class Supplier {
    static async getAll(search = '') {
        let query = 'SELECT * FROM supplier_master WHERE 1=1';
        const params = [];

        if (search) {
            query += ` AND (name LIKE ? OR code LIKE ?)`;
            params.push(`%${search}%`, `%${search}%`);
        }

        query += ' ORDER BY name ASC';

        const [rows] = await db.query(query, params);
        return rows;
    }

    static async create(data) {
        const {
            name, address, mobile_number, mobile_number_2, email,
            contact_person, contact_person_number, credit_limit, is_active, remark
        } = data;

        // Auto-generate supplier code
        const [lastSupplier] = await db.query(
            'SELECT code FROM supplier_master ORDER BY id DESC LIMIT 1'
        );

        let nextCode = 'SUP-001';
        if (lastSupplier.length > 0 && lastSupplier[0].code) {
            const lastNum = parseInt(lastSupplier[0].code.replace('SUP-', '')) || 0;
            nextCode = `SUP-${String(lastNum + 1).padStart(3, '0')}`;
        }

        const [result] = await db.query(
            `INSERT INTO supplier_master (
                code, name, address, mobile_number, mobile_number_2, email,
                contact_person, contact_person_number, credit_limit, is_active, remark
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                nextCode, name, address || '', mobile_number || '', mobile_number_2 || '',
                email || '', contact_person || '', contact_person_number || '',
                credit_limit || 0, is_active ? 1 : 0, remark || ''
            ]
        );

        return { ...result, code: nextCode };
    }

    static async update(id, data) {
        const {
            name, address, mobile_number, mobile_number_2, email,
            contact_person, contact_person_number, credit_limit, is_active, remark
        } = data;

        const [result] = await db.query(
            `UPDATE supplier_master SET
                name = ?, address = ?, mobile_number = ?, mobile_number_2 = ?, email = ?,
                contact_person = ?, contact_person_number = ?, credit_limit = ?,
                is_active = ?, remark = ?
            WHERE id = ?`,
            [
                name, address || '', mobile_number || '', mobile_number_2 || '',
                email || '', contact_person || '', contact_person_number || '',
                credit_limit || 0, is_active ? 1 : 0, remark || '', id
            ]
        );

        return result;
    }

    static async delete(id) {
        const [result] = await db.query('DELETE FROM supplier_master WHERE id = ?', [id]);
        return result;
    }
}

module.exports = Supplier;
