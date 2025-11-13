/*
  Legacy driver form logic has been replaced by the API-backed flow.
  This file now acts as a small shim that forwards the form submission to
  the new API endpoint at /driver_api/register.php using FormData.
  Keep this shim to preserve the original file path; maintainers should
  update frontends to call the API directly.
*/

document.addEventListener("DOMContentLoaded", function () {
  // Password toggle buttons: show/hide for password and confirm fields
  document.querySelectorAll(".password-toggle").forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault();
      const wrapper = button.parentElement;
      const input = wrapper.querySelector(
        "input[type='password'], input[type='text']"
      );
      if (!input) return;
      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      button.classList.toggle("visible", isPassword);
    });
  });

  const form = document.getElementById("driverForm");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    // Build formdata from existing form fields (including file)
    const fd = new FormData(form);
    try {
      const res = await fetch("../driver_api/register.php", {
        method: "POST",
        body: fd,
      });
      const data = await res.json();
      if (data.ok) {
        alert("Registration successful. Redirecting to dashboard...");
        window.location.href =
          data.redirect || "../driverDashboard/driverDashboard.php";
      } else if (data.conflict) {
        alert(
          "Registration failed - duplicate fields: " + data.conflict.join(", ")
        );
      } else {
        alert("Registration failed: " + (data.message || "Unknown error"));
      }
    } catch (err) {
      console.error(err);
      alert("An unexpected error occurred: " + err.message);
    }
  });
});
