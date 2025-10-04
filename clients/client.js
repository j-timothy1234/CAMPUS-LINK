document.getElementById("clientForm").addEventListener("submit", function(e) {
  e.preventDefault();

  // Simple password match validation
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;

  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return;
  }

  // For now, just alert success (replace with backend registration logic later)
  alert("Registration successful! Welcome to CampusLink.");
  this.reset();
});
