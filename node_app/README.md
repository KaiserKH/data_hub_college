# Data Hub College (Node App)

This folder contains a Node.js conversion of the PHP college management app.

## Features Implemented

- Session-based authentication with bcrypt password verification
- Role-based access middleware (`admin`, `teacher`, `student`)
- Admin-only APIs for users, classes, and notices
- Teacher API for own forms
- Student APIs for assigned forms and submission
- MySQL integration with the existing `college_data_hub` schema

## Setup

1. Copy env file:

```bash
cp .env.example .env
```

2. Install dependencies:

```bash
npm install
```

3. Run server:

```bash
npm run dev
```

The server starts at `http://localhost:3000` by default.

## API Routes

- `GET /api/health`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `GET /api/admin/dashboard` (admin only)
- `GET /api/admin/users` (admin only)
- `GET /api/admin/classes` (admin only)
- `GET /api/admin/notices` (admin only)
- `POST /api/admin/notices` (admin only)
- `GET /api/teacher/forms` (teacher only)
- `GET /api/student/forms` (student only)
- `POST /api/student/forms/:formId/submit` (student only)

## Notes

- PHP password hashes with `$2y$` are normalized to `$2b$` automatically so default admin credentials work.
- Use the existing DB seed user: `admin@college.local` / `Admin@123`.
