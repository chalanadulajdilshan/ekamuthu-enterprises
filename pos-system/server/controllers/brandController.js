const Brand = require('../models/Brand');

exports.index = async (req, res) => {
    try {
        const brands = await Brand.getAll();
        res.json({ success: true, data: brands });
    } catch (error) {
        console.error('BrandController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.categories = async (req, res) => {
    try {
        const categories = await Brand.getCategories();
        res.json({ success: true, data: categories });
    } catch (error) {
        console.error('BrandController.categories:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.store = async (req, res) => {
    try {
        const result = await Brand.create(req.body);
        res.json({ success: true, message: 'Brand created successfully', id: result.insertId });
    } catch (error) {
        console.error('BrandController.store:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.update = async (req, res) => {
    try {
        await Brand.update(req.params.id, req.body);
        res.json({ success: true, message: 'Brand updated successfully' });
    } catch (error) {
        console.error('BrandController.update:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
