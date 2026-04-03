const Product = require('../models/Product');
const { saveBase64Image } = require('./imageService');

/**
 * Fetch products with optional filters.
 */
const getAllProducts = async (filters) => {
    return await Product.getAll(filters);
};

/**
 * Create a new product, handling image upload.
 */
const createProduct = async (data) => {
    const imageFilename = saveBase64Image(data.image, data.code);
    return await Product.create({ ...data, image_file: imageFilename });
};

/**
 * Update an existing product, handling image upload.
 */
const updateProduct = async (id, data) => {
    const imageFilename = saveBase64Image(data.image, data.code);
    return await Product.update(id, { ...data, image_file: imageFilename });
};

module.exports = { getAllProducts, createProduct, updateProduct };
