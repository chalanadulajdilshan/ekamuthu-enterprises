const express = require('express');
const router = express.Router();
const grnController = require('../controllers/grnController');

router.get('/', grnController.index);
router.post('/', grnController.store);
router.get('/next-no', grnController.getNextNo);

module.exports = router;
