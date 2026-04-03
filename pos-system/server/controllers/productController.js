const { getAllProducts, createProduct, updateProduct, deleteProduct } = require('../services/productService');

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

exports.destroy = async (req, res) => {
    try {
        await deleteProduct(req.params.id);
        res.json({ success: true, message: 'Item deleted successfully' });
    } catch (error) {
        console.error('ProductController.destroy:', error);
        
        let message = 'Failed to delete item';
        if (error.code === 'ER_ROW_IS_REFERENCED_2' || error.errno === 1451) {
            message = 'Cannot delete this item because it has associated records (stock, sales, etc). Please deactivate it instead.';
        } else {
            message = error.message;
        }
        
        res.status(500).json({ success: false, message });
    }
};
