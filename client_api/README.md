# Client API

This folder contains a small REST API for client registration and helper endpoints.

Files:

- `register.php` — POST endpoint to register a client. Accepts JSON or form data, validates input, checks for duplicates, inserts into DB, creates a session and returns JSON with redirect.
- `check_unique.php` — GET endpoint to check if email/username/phone is already used.
- `utils.php` — helper functions (validation, ID generation).
- `register_form.html` — simple test form that posts to `register.php` using fetch (for manual testing).
- `EXAMPLES.md` — example fetch/curl commands.

Security notes:

- Use HTTPS in production.
- Restrict CORS origins in production.
- Keep secrets out of repo.
