const express = require('express');
const { listAssignedForms, submitForm } = require('../controllers/student.controller');
const { requireRole } = require('../middleware/auth');

const router = express.Router();

router.use(requireRole('student'));
router.get('/forms', listAssignedForms);
router.post('/forms/:formId/submit', submitForm);

module.exports = router;
