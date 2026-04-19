function ok(res, data = null, message = null, status = 200) {
  const payload = { success: true };
  if (data !== null) payload.data = data;
  if (message) payload.message = message;
  return res.status(status).json(payload);
}

function fail(res, message = 'Request failed', status = 400, details = null) {
  const payload = { success: false, message };
  if (details !== null) payload.details = details;
  return res.status(status).json(payload);
}

module.exports = {
  ok,
  fail
};
