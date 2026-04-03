const express = require('express');
const router = express.Router();
const supplierController = require('../controllers/supplierController');

router.get('/', supplierController.index);
router.post('/', supplierController.store);
router.put('/:id', supplierController.update);

module.exports = router;
