# Driver API

This folder contains a REST API for driver registration and helper endpoints.

Files:

- `utils.php` — validation helpers, ID generation, json helper.
- `register.php` — POST endpoint handling multipart/form-data registration (including photo upload). Performs validation, duplicate checks, inserts driver record, creates session and returns JSON with redirect to dashboard.
- `check_unique.php` — GET endpoint to verify availability of email/username/phone/plate.
- `register_form.html` — test form that uploads to `register.php` (multipart) and shows response.
- `EXAMPLES.md` — curl/fetch examples.

Security notes:

- Use HTTPS in production and restrict CORS.
- Limit allowed origins and file upload size.
