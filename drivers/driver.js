// ===== Listen for form submission =====
document.getElementById("driverForm").addEventListener("submit", function (event) {
  event.preventDefault(); // Prevent default form reload

  // ===== Collect input values =====
  const username = document.getElementById("username").value.trim();
  const email = document.getElementById("email").value.trim();

  let phone_number = document.getElementById("phone").value.trim();
  phone_number = phone_number.replace(/\D/g, ""); // remove non-digit chars
  if (phone_number.length === 9 && phone_number.startsWith("7")) {
      phone_number = "0" + phone_number; // auto-add 0 for 9-digit numbers
  }

  const gender = document.querySelector('input[name="gender"]:checked')?.value;
  const car_plate_number = document.getElementById("plate").value.trim().toUpperCase();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;
  const residence = document.getElementById("residence").value.trim();
  const profile_photo = document.getElementById("photo").files[0];

  // ===== Validation Regex =====
  const nameRegex = /^[A-Za-z ]+$/; // Only letters and spaces
  const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/; // Must end with @gmail.com
  const phoneRegex = /^(070|071|074|075|076|077|078)\d{7}$/; // Ugandan 10-digit numbers
  const plateRegex = /^U[A-Z]{1,2}\s\d{3}[A-Z]{1,2}$/; // Simplified plate format
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/; // Strong password

  // ===== Input Validation =====
  if (!nameRegex.test(username)) {
    alert("Username should contain only letters and spaces.");
    return;
  }

  if (!emailRegex.test(email)) {
    alert("Email must be a valid Gmail address (jto@gmail.com).");
    return;
  }

  if (!phoneRegex.test(phone_number)) {
    alert("Phone number must start with 070, 071, 074, 075, 076, 077, or 078 and contain 10 digits.");
    return;
  }

  if (!gender) {
    alert("Please select Gender ('MALE', 'FEMALE').");
    return;
  }

  if (!plateRegex.test(car_plate_number)) {
    alert("Invalid plate number format. Use formats like UMA 000G, UMA 000MM, or UA 000AA.");
    return;
  }

  if (!profile_photo) {
    alert("Please upload a profile photo.");
    return;
  }

  if (profile_photo.size > 5 * 1024 * 1024) {
    alert("Profile photo must not exceed 5MB.");
    return;
  }

  if (!passwordRegex.test(password)) {
    alert("Password must be at least 8 characters, include uppercase, lowercase, numbers, and special characters.");
    return;
  }

  if (password !== confirmPassword) {
    alert("Passwords do not match!");
    return;
  }

  // ===== Prepare FormData for AJAX =====
  const formData = new FormData(); // FIXED: Added missing declaration
  formData.append("username", username);
  formData.append("email", email);
  formData.append("phone", phone_number);
  formData.append("gender", gender);
  formData.append("plate", car_plate_number);
  formData.append("residence", residence);
  formData.append("password", password);
  formData.append("photo", profile_photo);

  // ===== Send data to backend =====
  fetch("driver.php", {
    method: "POST",
    body: formData
  })

  .then(response => response.json())
  .then(data => {

    // In driver.js, update the success handler:
  if (data.status === "success") {
    
    alert("Driver registered successfully!");
    document.getElementById("driverForm").reset();
    document.getElementById("photoPreview").classList.add("d-none");
    // Redirect to login page instead of direct dashboard
    window.location.href = "../login/login.html";

    } else {

      alert("Error: " + data.message);
    }

  })

  .catch(error => {
    console.error("Error:", error);
    alert("An unexpected error occurred. Please try again.");

  });

});

// ===== Handle photo preview =====
document.getElementById("photo").addEventListener("change", function (event) {
  const file = event.target.files[0];
  const preview = document.getElementById("photoPreview");

  if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
      preview.classList.remove("d-none");
    };

    reader.readAsDataURL(file);
  } else {
    preview.classList.add("d-none");
  }

});

// ===== AJAX Live Availability Check =====
function checkAvailability(fieldId, type) {
  const value = document.getElementById(fieldId).value.trim();
  if (!value) return;

  $.ajax({
    url: "check_driver.php",
    type: "POST",
    dataType: "json",
    data: { field: type, value: value }, // FIXED: changed 'type' to 'field'
    success: function(response) {
      const statusSpan = document.getElementById(fieldId + "Status");
      if (!statusSpan) {
        // Create status span if it doesn't exist
        const statusSpan = document.createElement('span');
        statusSpan.id = fieldId + "Status";
        statusSpan.className = "availability-status";
        document.getElementById(fieldId).parentNode.appendChild(statusSpan);
      }

      if (response === "exists") {
        statusSpan.textContent = `${type} already exists`;
        statusSpan.style.color = "red";
      } else {
        statusSpan.textContent = `${type} available`;
        statusSpan.style.color = "green";
      }
    },

    error: function() {
      console.error("Error checking " + type);
    }

  });

}

// Attach live check to email, phone, plate
document.getElementById("email").addEventListener("blur", () => checkAvailability("email", "Email"));
document.getElementById("phone").addEventListener("blur", () => checkAvailability("phone", "Phone_Number"));
document.getElementById("plate").addEventListener("blur", () => checkAvailability("plate", "Car_Plate_Number"));