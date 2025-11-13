# Client API Examples

Registration (JSON):

```bash
curl -i -X POST http://localhost/CAMPUS-LINK/client_api/register.php \
  -H "Content-Type: application/json" \
  -d '{"username":"johndoe","email":"johndoe@gmail.com","phone_number":"0712345678","gender":"Male","password":"Secret123!"}'
```

Check availability (username):

```bash
curl -i "http://localhost/CAMPUS-LINK/client_api/check_unique.php?username=johndoe"
```

Notes:

- The register endpoint will create a PHP session for the user and return a `redirect` URL on success.
- Always use HTTPS in production and limit CORS.
