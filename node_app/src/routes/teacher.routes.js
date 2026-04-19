const express = require('express');
const { listOwnForms } = require('../controllers/teacher.controller');
const { requireRole } = require('../middleware/auth');

const router = express.Router();

router.use(requireRole('teacher'));
router.get('/forms', listOwnForms);

module.exports = router;
