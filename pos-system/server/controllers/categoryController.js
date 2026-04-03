const Category = require('../models/Category');

exports.index = async (req, res) => {
    try {
        const categories = await Category.getAll();
        res.json({ success: true, data: categories });
    } catch (error) {
        console.error('CategoryController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
