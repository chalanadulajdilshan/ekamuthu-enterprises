const Supplier = require('../models/Supplier');

exports.index = async (req, res) => {
    try {
        const search = req.query.search || '';
        const suppliers = await Supplier.getAll(search);
        res.json({ success: true, data: suppliers });
    } catch (error) {
        console.error('SupplierController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.store = async (req, res) => {
    try {
        const result = await Supplier.create(req.body);
        res.json({ success: true, message: 'Supplier created successfully', id: result.insertId, code: result.code });
    } catch (error) {
        console.error('SupplierController.store:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.update = async (req, res) => {
    try {
        await Supplier.update(req.params.id, req.body);
        res.json({ success: true, message: 'Supplier updated successfully' });
    } catch (error) {
        console.error('SupplierController.update:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
