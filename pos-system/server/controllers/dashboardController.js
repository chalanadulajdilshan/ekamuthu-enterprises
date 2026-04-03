const Dashboard = require('../models/Dashboard');

exports.stats = async (req, res) => {
    try {
        const stats = await Dashboard.getStats();
        res.json({ success: true, data: stats });
    } catch (error) {
        console.error('DashboardController.stats:', error);
        res.status(500).json({ success: false, message: error.message });
    }
};
