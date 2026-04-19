const express = require('express');
const helmet = require('helmet');
const morgan = require('morgan');
const session = require('express-session');
const { port, sessionSecret, nodeEnv } = require('./config/env');
const { query } = require('./config/db');
const { loadCurrentUser } = require('./middleware/auth');
const { ok, fail } = require('./utils/response');

const authRoutes = require('./routes/auth.routes');
const adminRoutes = require('./routes/admin.routes');
const teacherRoutes = require('./routes/teacher.routes');
const studentRoutes = require('./routes/student.routes');

const app = express();

app.use(helmet());
app.use(morgan('dev'));
app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ extended: false }));

app.use(
  session({
    name: 'dhc.sid',
    secret: sessionSecret,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      sameSite: 'lax',
      secure: false,
      maxAge: 1000 * 60 * 60
    }
  })
);

app.use(loadCurrentUser);

app.get('/api/health', async (_req, res, next) => {
  try {
    await query('SELECT 1 AS ok');
    return ok(res, { status: 'up', db: 'connected', env: nodeEnv });
  } catch (error) {
    return next(error);
  }
});

app.use('/api/auth', authRoutes);
app.use('/api/admin', adminRoutes);
app.use('/api/teacher', teacherRoutes);
app.use('/api/student', studentRoutes);

app.use((req, res) => fail(res, `Route not found: ${req.method} ${req.originalUrl}`, 404));

app.use((err, _req, res, _next) => {
  console.error(err);
  return fail(res, 'Internal server error', 500, nodeEnv === 'development' ? err.message : null);
});

app.listen(port, () => {
  console.log(`Node app listening on http://localhost:${port}`);
});
