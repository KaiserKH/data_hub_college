const { query } = require('../config/db');
const { ok, fail } = require('../utils/response');
const { verifyPassword } = require('../utils/password');

async function login(req, res, next) {
  try {
    const email = String(req.body?.email || '').trim().toLowerCase();
    const password = String(req.body?.password || '');

    if (!email || !password) {
      return fail(res, 'Email and password are required', 422);
    }

    const users = await query(
      'SELECT id, name, email, password, role, is_active FROM users WHERE email = ? LIMIT 1',
      [email]
    );

    if (users.length !== 1 || Number(users[0].is_active) !== 1) {
      return fail(res, 'Invalid email or password', 401);
    }

    const user = users[0];
    const valid = await verifyPassword(password, user.password);

    if (!valid) {
      return fail(res, 'Invalid email or password', 401);
    }

    req.session.userId = user.id;
    req.session.userRole = user.role;

    await query('UPDATE users SET last_login = NOW() WHERE id = ?', [user.id]);

    return ok(res, {
      id: user.id,
      name: user.name,
      email: user.email,
      role: user.role
    }, 'Login successful');
  } catch (error) {
    return next(error);
  }
}

function logout(req, res) {
  if (!req.session) {
    return ok(res, null, 'Logged out');
  }

  req.session.destroy(() => {
    res.clearCookie('connect.sid');
    return ok(res, null, 'Logged out');
  });
}

function me(req, res) {
  if (!req.user) {
    return fail(res, 'Not logged in', 401);
  }

  return ok(res, {
    id: req.user.id,
    name: req.user.name,
    email: req.user.email,
    role: req.user.role,
    class_id: req.user.class_id,
    roll_number: req.user.roll_number
  });
}

module.exports = {
  login,
  logout,
  me
};
