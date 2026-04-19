const { query } = require('../config/db');
const { fail } = require('../utils/response');

async function loadCurrentUser(req, _res, next) {
  req.user = null;
  const userId = req.session?.userId;
  if (!userId) return next();

  try {
    const rows = await query(
      'SELECT id, name, email, role, class_id, roll_number, is_active FROM users WHERE id = ? LIMIT 1',
      [userId]
    );

    if (rows.length === 1 && Number(rows[0].is_active) === 1) {
      req.user = rows[0];
    }
  } catch (error) {
    return next(error);
  }

  return next();
}

function requireAuth(req, res, next) {
  if (!req.user) {
    return fail(res, 'Authentication required', 401);
  }
  return next();
}

function requireRole(role) {
  return (req, res, next) => {
    if (!req.user) {
      return fail(res, 'Authentication required', 401);
    }

    if (req.user.role !== role) {
      return fail(res, 'Forbidden', 403);
    }

    return next();
  };
}

module.exports = {
  loadCurrentUser,
  requireAuth,
  requireRole
};
