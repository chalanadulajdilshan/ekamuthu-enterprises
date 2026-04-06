const Grn = require('../models/Grn');

exports.index = async (req, res) => {
    try {
        const grns = await Grn.getAll();
        res.json({ success: true, data: grns });
    } catch (error) {
        console.error('GrnController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.store = async (req, res) => {
    try {
        const result = await Grn.create(req.body);
        res.json({ success: true, message: 'GRN created successfully', data: result });
    } catch (error) {
        console.error('GrnController.store:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.getNextNo = async (req, res) => {
    try {
        const nextNo = await Grn.getNextGrnNo();
        res.json({ success: true, data: nextNo });
    } catch (error) {
        console.error('GrnController.getNextNo:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
