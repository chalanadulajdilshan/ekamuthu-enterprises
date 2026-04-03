const express = require('express');
const router = express.Router();
const saleController = require('../controllers/saleController');

router.post('/', saleController.store);
router.get('/recent', saleController.recent);
router.get('/:id', saleController.show);

module.exports = router;
