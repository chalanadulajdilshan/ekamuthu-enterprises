const { getAllProducts, createProduct, updateProduct } = require('../services/productService');

exports.index = async (req, res) => {
    try {
        const filters = {
            search: req.query.search || '',
            category: req.query.category || '',
            all: req.query.all === 'true'
        };
        const products = await getAllProducts(filters);
        res.json({ success: true, data: products });
    } catch (error) {
        console.error('ProductController.index:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.store = async (req, res) => {
    try {
        const result = await createProduct(req.body);
        res.json({ success: true, message: 'Item created successfully', id: result.insertId });
    } catch (error) {
        console.error('ProductController.store:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};

exports.update = async (req, res) => {
    try {
        await updateProduct(req.params.id, req.body);
        res.json({ success: true, message: 'Item updated successfully' });
    } catch (error) {
        console.error('ProductController.update:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
