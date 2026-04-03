const Sale = require('../models/Sale');

/**
 * Create a new POS sale with transaction support.
 */
const createSale = async (data) => {
    return await Sale.create(data);
};

/**
 * Get recent POS sales.
 */
const getRecentSales = async () => {
    return await Sale.getRecent();
};

/**
 * Get full sale details by ID.
 */
const getSaleDetails = async (id) => {
    return await Sale.getById(id);
};

module.exports = { createSale, getRecentSales, getSaleDetails };
