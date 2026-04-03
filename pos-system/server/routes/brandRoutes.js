const express = require('express');
const router = express.Router();
const brandController = require('../controllers/brandController');

router.get('/', brandController.index);
router.get('/categories', brandController.categories);
router.post('/', brandController.store);
router.put('/:id', brandController.update);

module.exports = router;
