# CampusLink API examples

This file contains short examples showing how to call the new RESTful auth endpoint at `api/v1/auth.php`.

Base URL (if running locally under XAMPP):

http://localhost/CAMPUS-LINK/api/v1/auth.php

1. JavaScript `fetch` (JSON) - login

```javascript
// Example: AJAX login using fetch with JSON body
async function login(identifier, password) {
  const res = await fetch("/CAMPUS-LINK/api/v1/auth.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ identifier, password }),
  });

  const data = await res.json();
  if (data.ok) {
    // The server sets the PHP session cookie; in the browser the cookie is sent automatically
    // Redirect user to the returned dashboard URL
    window.location.href = data.redirect;
  } else {
    console.error("Login failed", data.message);
    // Show error to user
  }
}

// Usage: login('timothy@example.com', 'secret');
```

2. JavaScript `fetch` (form POST) - compatibility

```javascript
// If you prefer to send as form data (like the HTML form), do this:
async function loginForm(identifier, password) {
  const fd = new URLSearchParams();
  fd.append("email", identifier);
  fd.append("password", password);

  const res = await fetch("/CAMPUS-LINK/api/v1/auth.php", {
    method: "POST",
    headers: { Accept: "application/json" },
    body: fd,
  });
  const data = await res.json();
  console.log(data);
}
```

3. Curl - login (JSON)

```bash
curl -i -X POST http://localhost/CAMPUS-LINK/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"alice@example.com","password":"secret"}'
```

Note: Curl won't keep the PHP session cookie unless you save and reuse cookies with `-c` and `-b` options.

4. Node.js (axios) example

```js
const axios = require("axios");

async function loginNode(identifier, password) {
  try {
    const res = await axios.post(
      "http://localhost/CAMPUS-LINK/api/v1/auth.php",
      {
        identifier,
        password,
      },
      { withCredentials: true }
    );
    console.log(res.data);
  } catch (err) {
    console.error(err.response ? err.response.data : err.message);
  }
}
```

5. Check session status (GET)

```bash
curl -i http://localhost/CAMPUS-LINK/api/v1/auth.php
```

6. Logout (DELETE)

```bash
curl -i -X DELETE http://localhost/CAMPUS-LINK/api/v1/auth.php
```

Security & notes

- The endpoint sets standard PHP session cookies; browsers will send them automatically.
- For API clients that cannot use cookies (native mobile apps or third-party services), implement a token-based flow (JWT or server-issued API tokens).
- Always use HTTPS in production and restrict `Access-Control-Allow-Origin` to your allowed frontends.
