const { query } = require('../config/db');
const { ok, fail } = require('../utils/response');

async function dashboard(req, res, next) {
  try {
    const [users, forms, notices] = await Promise.all([
      query('SELECT COUNT(*) AS total FROM users'),
      query('SELECT COUNT(*) AS total FROM forms'),
      query('SELECT COUNT(*) AS total FROM notices WHERE is_active = 1')
    ]);

    return ok(res, {
      users: Number(users[0].total || 0),
      forms: Number(forms[0].total || 0),
      activeNotices: Number(notices[0].total || 0)
    });
  } catch (error) {
    return next(error);
  }
}

async function listUsers(_req, res, next) {
  try {
    const users = await query(
      'SELECT id, name, email, role, class_id, roll_number, is_active, last_login, created_at FROM users ORDER BY id DESC'
    );
    return ok(res, users);
  } catch (error) {
    return next(error);
  }
}

async function listClasses(_req, res, next) {
  try {
    const classes = await query('SELECT id, name, description, created_at FROM classes ORDER BY name ASC');
    return ok(res, classes);
  } catch (error) {
    return next(error);
  }
}

async function listNotices(_req, res, next) {
  try {
    const notices = await query(
      `SELECT n.id, n.title, n.content, n.audience, n.is_active, n.created_at, u.name AS published_by_name
       FROM notices n
       LEFT JOIN users u ON u.id = n.published_by
       ORDER BY n.created_at DESC`
    );
    return ok(res, notices);
  } catch (error) {
    return next(error);
  }
}

async function createNotice(req, res, next) {
  try {
    const title = String(req.body?.title || '').trim();
    const content = String(req.body?.content || '').trim();
    const audience = String(req.body?.audience || 'all').trim();

    if (!title || !content) {
      return fail(res, 'Title and content are required', 422);
    }

    const validAudience = ['all', 'admin', 'teacher', 'student'];
    if (!validAudience.includes(audience)) {
      return fail(res, 'Invalid audience', 422);
    }

    const result = await query(
      'INSERT INTO notices (title, content, audience, is_active, published_by, created_at) VALUES (?, ?, ?, 1, ?, NOW())',
      [title, content, audience, req.user.id]
    );

    return ok(res, { id: result.insertId }, 'Notice created', 201);
  } catch (error) {
    return next(error);
  }
}

module.exports = {
  dashboard,
  listUsers,
  listClasses,
  listNotices,
  createNotice
};
