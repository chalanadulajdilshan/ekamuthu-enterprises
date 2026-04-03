const Customer = require('../models/Customer');

exports.index = async (req, res) => {
    try {
        const search = req.query.search || '';
        const customers = await Customer.getAll(search);
        res.json({ success: true, data: customers });
    } catch (error) {
        console.error('CustomerController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
