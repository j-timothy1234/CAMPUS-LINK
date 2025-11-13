// Password visibility toggle + form submission for client form
document.addEventListener("DOMContentLoaded", function () {
  // Toggle buttons
  document.querySelectorAll(".password-toggle").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const wrapper = btn.parentElement;
      const input = wrapper.querySelector(
        "input[type='password'], input[type='text']"
      );
      if (!input) return;
      const isPwd = input.type === "password";
      input.type = isPwd ? "text" : "password";
      btn.classList.toggle("visible", isPwd);
    });
  });

  // Form submission (moved from inline script)
  const form = document.getElementById("clientForm");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const resultEl = document.getElementById("result");
    resultEl.textContent = "Registering...";
    btn.disabled = true;
    try {
      const password = document.getElementById("password").value;
      const confirm = document.getElementById("confirmPassword").value;
      if (password !== confirm) {
        resultEl.textContent = "Passwords do not match.";
        btn.disabled = false;
        return;
      }

      const payload = {
        username: document.getElementById("username").value.trim(),
        email: document.getElementById("email").value.trim(),
        phone_number: document.getElementById("phone_number").value.trim(),
        gender: document.getElementById("gender").value,
        password: password,
      };

      const res = await fetch("../client_api/register.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        credentials: "same-origin",
      });
      const data = await res.json();
      resultEl.textContent = data.message || JSON.stringify(data);
      if (data.ok && data.redirect)
        setTimeout(() => (window.location.href = data.redirect), 800);
    } catch (err) {
      resultEl.textContent = "Error: " + (err.message || err);
    } finally {
      btn.disabled = false;
    }
  });
});
