const { query, pool } = require('../config/db');
const { ok, fail } = require('../utils/response');

async function listAssignedForms(req, res, next) {
  try {
    const forms = await query(
      `SELECT f.id, f.uuid, f.title, f.description, f.status, f.deadline, f.created_at,
              u.name AS teacher_name,
              EXISTS(SELECT 1 FROM responses r WHERE r.form_id = f.id AND r.user_id = ?) AS submitted
       FROM forms f
       LEFT JOIN users u ON u.id = f.teacher_id
       WHERE f.class_id = ? AND f.status = 'active'
       ORDER BY f.created_at DESC`,
      [req.user.id, req.user.class_id]
    );

    return ok(res, forms);
  } catch (error) {
    return next(error);
  }
}

async function submitForm(req, res, next) {
  const connection = await pool.getConnection();
  try {
    const formId = Number(req.params.formId);
    const answers = Array.isArray(req.body?.answers) ? req.body.answers : [];

    if (!Number.isInteger(formId) || formId <= 0) {
      return fail(res, 'Invalid form id', 422);
    }

    if (answers.length === 0) {
      return fail(res, 'Answers are required', 422);
    }

    await connection.beginTransaction();

    const [forms] = await connection.execute(
      'SELECT id, class_id, status, deadline FROM forms WHERE id = ? LIMIT 1',
      [formId]
    );

    if (forms.length !== 1) {
      await connection.rollback();
      return fail(res, 'Form not found', 404);
    }

    const form = forms[0];
    if (Number(form.class_id) !== Number(req.user.class_id)) {
      await connection.rollback();
      return fail(res, 'You are not allowed to submit this form', 403);
    }

    if (form.status !== 'active') {
      await connection.rollback();
      return fail(res, 'Form is not active', 422);
    }

    if (form.deadline && new Date(form.deadline) < new Date()) {
      await connection.rollback();
      return fail(res, 'Form deadline has passed', 422);
    }

    const [existing] = await connection.execute(
      'SELECT id FROM responses WHERE form_id = ? AND user_id = ? LIMIT 1',
      [formId, req.user.id]
    );

    let responseId;
    if (existing.length === 1) {
      responseId = existing[0].id;
      await connection.execute('DELETE FROM answers WHERE response_id = ?', [responseId]);
      await connection.execute(
        'UPDATE responses SET is_complete = 1, submitted_at = NOW(), updated_at = CURRENT_TIMESTAMP WHERE id = ?',
        [responseId]
      );
    } else {
      const [responseResult] = await connection.execute(
        'INSERT INTO responses (form_id, user_id, is_complete, submitted_at, created_at) VALUES (?, ?, 1, NOW(), NOW())',
        [formId, req.user.id]
      );
      responseId = responseResult.insertId;
    }

    for (const answer of answers) {
      const questionId = Number(answer.question_id);
      if (!Number.isInteger(questionId) || questionId <= 0) {
        continue;
      }

      const answerText = answer.answer_text == null ? null : String(answer.answer_text);
      await connection.execute(
        'INSERT INTO answers (response_id, question_id, answer_text, created_at) VALUES (?, ?, ?, NOW())',
        [responseId, questionId, answerText]
      );
    }

    await connection.commit();
    return ok(res, { response_id: responseId }, 'Form submitted');
  } catch (error) {
    try {
      await connection.rollback();
    } catch (_rollbackError) {
      // Keep original error
    }
    return next(error);
  } finally {
    connection.release();
  }
}

module.exports = {
  listAssignedForms,
  submitForm
};
