const { query } = require('../config/db');
const { ok } = require('../utils/response');

async function listOwnForms(req, res, next) {
  try {
    const forms = await query(
      `SELECT f.id, f.uuid, f.class_id, c.name AS class_name, f.title, f.description, f.status, f.deadline, f.created_at
       FROM forms f
       LEFT JOIN classes c ON c.id = f.class_id
       WHERE f.teacher_id = ?
       ORDER BY f.created_at DESC`,
      [req.user.id]
    );

    return ok(res, forms);
  } catch (error) {
    return next(error);
  }
}

module.exports = {
  listOwnForms
};
