const Company = require('../models/Company');

exports.show = async (req, res) => {
    try {
        const company = await Company.getProfile();
        res.json({ success: true, data: company });
    } catch (error) {
        console.error('CompanyController.show:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
