const express = require('express');
const {
  dashboard,
  listUsers,
  listClasses,
  listNotices,
  createNotice
} = require('../controllers/admin.controller');
const { requireRole } = require('../middleware/auth');

const router = express.Router();

router.use(requireRole('admin'));

router.get('/dashboard', dashboard);
router.get('/users', listUsers);
router.get('/classes', listClasses);
router.get('/notices', listNotices);
router.post('/notices', createNotice);

module.exports = router;
