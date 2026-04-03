const express = require('express');
const router = express.Router();

// Import all route modules
const productRoutes    = require('./productRoutes');
const categoryRoutes   = require('./categoryRoutes');
const brandRoutes      = require('./brandRoutes');
const supplierRoutes   = require('./supplierRoutes');
const saleRoutes       = require('./saleRoutes');
const customerRoutes   = require('./customerRoutes');
const companyRoutes    = require('./companyRoutes');
const dashboardRoutes  = require('./dashboardRoutes');

// Mount routes
router.use('/products',        productRoutes);
router.use('/categories',      categoryRoutes);
router.use('/brands',          brandRoutes);
router.use('/brand-categories', require('./brandCategoryRoutes'));
router.use('/suppliers',       supplierRoutes);
router.use('/sales',           saleRoutes);
router.use('/customers',       customerRoutes);
router.use('/company',         companyRoutes);
router.use('/departments',     require('./departmentRoutes'));
router.use('/payment-types',   require('./paymentTypeRoutes'));
router.use('/dashboard-stats', dashboardRoutes);

module.exports = router;
