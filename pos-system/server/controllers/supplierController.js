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
        
        // Handle duplicate entry (MySQL Error 1062)
        if (error.errno === 1062 || error.code === 'ER_DUP_ENTRY') {
            const field = error.sqlMessage.includes('idx_supplier_mobile') ? 'mobile number' : 'code';
            return res.status(400).json({ 
                success: false, 
                message: `A supplier with this ${field} already exists. Please use a unique value.` 
            });
        }

        res.status(500).json({ success: false, message: 'Internal server error while creating supplier' });
    }
};

exports.update = async (req, res) => {
    try {
        await Supplier.update(req.params.id, req.body);
        res.json({ success: true, message: 'Supplier updated successfully' });
    } catch (error) {
        console.error('SupplierController.update:', error);

        // Handle duplicate entry (MySQL Error 1062)
        if (error.errno === 1062 || error.code === 'ER_DUP_ENTRY') {
            const field = error.sqlMessage.includes('idx_supplier_mobile') ? 'mobile number' : 'code';
            return res.status(400).json({ 
                success: false, 
                message: `Another supplier already uses this ${field}. Please use a unique value.` 
            });
        }

        res.status(500).json({ success: false, message: 'Internal server error while updating supplier' });
    }
};

exports.destroy = async (req, res) => {
    try {
        await Supplier.delete(req.params.id);
        res.json({ success: true, message: 'Supplier deleted successfully' });
    } catch (error) {
        console.error('SupplierController.destroy:', error);
        
        // Handle foreign key constraint (MySQL Error 1451)
        if (error.errno === 1451 || error.code === 'ER_ROW_IS_REFERENCED_2') {
            return res.status(400).json({ 
                success: false, 
                message: 'This supplier cannot be deleted because it is referenced in GRN records or other transactions.' 
            });
        }

        res.status(500).json({ success: false, message: 'Internal server error while deleting supplier' });
    }
};
