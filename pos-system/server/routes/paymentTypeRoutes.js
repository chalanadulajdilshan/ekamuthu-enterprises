const express = require('express');
const router = express.Router();
const db = require('../config/db');

router.get('/', async (req, res) => {
    try {
        const [rows] = await db.query('SELECT * FROM payment_type ORDER BY name ASC');
        res.json({ success: true, data: rows });
    } catch (error) {
        console.error('PaymentTypeRoutes.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
});

module.exports = router;
