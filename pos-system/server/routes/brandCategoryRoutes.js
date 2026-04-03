const express = require('express');
const router = express.Router();
const brandController = require('../controllers/brandController');

// GET /api/brand-categories → returns brand categories
router.get('/', brandController.categories);

module.exports = router;
