function openLogin() {
  let width = 450;
  let height = 600;

  // Adjust size based on screen width
  if (window.innerWidth < 576) {
    width = window.innerWidth - 40;
    height = window.innerHeight - 80;
  }

  const left = (screen.width - width) / 2;
  const top = (screen.height - height) / 2;

  window.open(
    "login.html",
    "LoginWindow",
    `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
  );
}

function redirectRegister() {
  const select = document.getElementById("registerSelect");
  const page = select.value;
  if (page) {
    window.location.href = "../register/" + page; // adjust path if needed
  }
}

// Password toggle for login page
(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const btn = document.querySelector(".password-toggle");
    if (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const wrapper = btn.parentElement;
        const input = wrapper.querySelector(
          "input[type='password'], input[type='text']"
        );
        if (!input) return;
        const isPassword = input.type === "password";
        input.type = isPassword ? "text" : "password";
        btn.classList.toggle("visible", isPassword);
      });
    }
  });
})();
