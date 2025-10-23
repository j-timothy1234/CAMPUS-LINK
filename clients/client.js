
$(document).ready(function () {
  $("#clientForm").on("submit", function (e) {
    e.preventDefault();

    // Get form values
    const username = $("#username").val().trim();
    const email = $("#email").val().trim();
    const phone_number = $("#phone_number").val().trim();
    const gender = $("input[name='gender']:checked").val();
    const password = $("#password").val();
    const confirmPassword = $("#confirmPassword").val();

    // Username: letters, Spaces, Special characters ie Job Tim, J-Tim
    if (!/^[A-Za-z][A-Za-z\s\-']*[A-Za-z]$/.test(username)) {
      alert("Username must start and end with a letter, and can contain spaces, hyphens, or apostrophes(e.g., Job Timothy, J-Timothy, O'Connor).");
      return;
    }

    // Email: small letters and must end with @gmail.com
    if (!/^[a-z0-9]+@gmail\.com$/.test(email)) {
      alert("Email must be a valid @gmail.com address.");
      return;
    }

    // Telephone: must be digits and at least 8 characters
    if (!/^\d{10,}$/.test(phone_number)) {
      alert("Telephone number must be at least 10 digits.");
      return;
    }

    // Gender: must be selected
    if (!gender) {
      alert("Please select your gender.");
      return;
    }

    // Password: at least 8 chars, upper, lower, number, special char
    if (
      !/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).*$/.test(password)
    ) {
      alert(
        "Password must be at least 8 characters and include upper and lowercase letters, a number, and a special character."
      );
      return;
    }

    // Passwords match
    if (password !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }

    // Send data via AJAX to PHP backend
    $.ajax({
      url: "client.php",
      type: "POST",
      dataType: "json",
      data: {
        username: username,
        email: email,
        phone_number: phone_number,
        gender: gender,
        password: password,
      },
      success: function (response) {
        if (response.success) {
          alert("Registration successful! Welcome to CampusLink.");
          window.location.href = "../clientDashboard/clientDashboard.php";
          $("#clientForm")[0].reset();
        } else {
          alert("Registration failed: " + response.message);
        }
      },
      error: function () {

        alert("An error occurred. Please try again.");

      },

    });

  });

});