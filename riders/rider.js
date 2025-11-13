// ===== Password Visibility Toggle =====
document.addEventListener("DOMContentLoaded", function () {
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

  // ===== Listen for form submission =====
  /*
    Legacy rider form logic is now handled by the rider_api.
    This script acts as a compatibility shim that forwards the form to the
    new API endpoint at /rider_api/register.php using FormData.
  */
  const form = document.getElementById("riderForm");
  if (!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();
    const fd = new FormData(form);
    try {
      const res = await fetch("../rider_api/register.php", {
        method: "POST",
        body: fd,
      });
      const data = await res.json();
      if (data.ok) {
        alert("Registration successful. Redirecting to dashboard...");
        window.location.href =
          data.redirect || "../riderDashboard/riderDashboard.php";
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
