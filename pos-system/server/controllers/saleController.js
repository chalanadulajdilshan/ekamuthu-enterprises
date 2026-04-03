const { createSale, getRecentSales, getSaleDetails } = require('../services/saleService');

exports.store = async (req, res) => {
    try {
        const result = await createSale(req.body);
        res.json({ success: true, message: 'Sale completed successfully', data: result });
    } catch (error) {
        console.error('SaleController.store:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.recent = async (req, res) => {
    try {
        const sales = await getRecentSales();
        res.json({ success: true, data: sales });
    } catch (error) {
        console.error('SaleController.recent:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.show = async (req, res) => {
    try {
        const sale = await getSaleDetails(req.params.id);
        if (!sale) {
            return res.status(404).json({ success: false, message: 'Invoice not found' });
        }
        res.json({ success: true, data: sale });
    } catch (error) {
        console.error('SaleController.show:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
