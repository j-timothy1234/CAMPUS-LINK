document.getElementById("riderForm").addEventListener("submit", function(event) {
  event.preventDefault();

  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return;
  }

  alert("Rider registration successful!");
  // TODO: Send data + photo to your backend
});

// Handle photo preview
document.getElementById("photo").addEventListener("change", function(event) {
  const file = event.target.files[0];
  const preview = document.getElementById("photoPreview");

  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.classList.remove("hidden");
    };
    reader.readAsDataURL(file);
  }
});
