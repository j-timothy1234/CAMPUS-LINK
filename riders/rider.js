// ===== Listen for form submission =====
document.getElementById("riderForm").addEventListener("submit", function (event) {
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
  const motorcycle_plate = document.getElementById("plate").value.trim().toUpperCase();
  const residence = document.getElementById("residence").value.trim();
  const password = document.getElementById("password").value;
  const confirmPassword = document.getElementById("confirmPassword").value;
  const profile_photo = document.getElementById("photo").files[0];

  // ===== Validation Regex =====
  const nameRegex = /^[A-Za-z ]+$/; // Only letters and spaces
  const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/; // Must end with @gmail.com
  const phoneRegex = /^(070|071|074|075|076|077|078)\d{7}$/; // Ugandan 10-digit numbers

  // Motorcycle plate validation: UAA 000M or UA 000AA
  const plateRegex = /^(U[A-Z]{2} \d{3}[A-Z]{1}|U[A-Z]{1} \d{3}[A-Z]{2})$/;

  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/; // Strong password

  // ===== Input Validation =====
  if (!nameRegex.test(username)) {
    alert("Username should contain only letters and spaces.");
    return;
  }

  if (!emailRegex.test(email)) {
    alert("Email must be a valid Gmail address (example@gmail.com).");
    return;
  }

  if (!phoneRegex.test(phone_number)) {
    alert("Phone number must start with 070, 071, 074, 075, 076, 077, or 078 and contain 10 digits.");
    return;
  }

  if (!gender) {
    alert("Please select a gender.");
    return;
  }

  if (!plateRegex.test(motorcycle_plate)) {
    alert("Invalid motorcycle plate number format. Use formats like:\n• UAA 000M (e.g., UBA 123A)\n• UA 000AA (e.g., UA 456BB)");
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

  if (!residence) {
    alert("Please enter your place of residence.");
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
  const formData = new FormData();
  formData.append("username", username);
  formData.append("email", email);
  formData.append("phone", phone_number);
  formData.append("gender", gender);
  formData.append("plate", motorcycle_plate);
  formData.append("residence", residence);
  formData.append("password", password);
  formData.append("photo", profile_photo);

  // ===== Send data to backend =====
  fetch("rider.php", {
    method: "POST",
    body: formData
  })

  .then(response => response.json())
  .then(data => {
    if (data.status === "success") {
      alert("Rider registered successfully!");
      document.getElementById("riderForm").reset();
      // Hide photo preview
      const preview = document.getElementById("photoPreview");
      if (preview) {
        preview.classList.add("hidden");
      }
         // After registration, redirect rider to the riders' login page
         // so they can enter email and password to log in
         window.location.href = "rider_login.html";
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
      preview.classList.remove("hidden");
    };
    reader.readAsDataURL(file);
  } else {
    preview.classList.add("hidden");
  }
});

// ===== AJAX Live Availability Check =====
function checkAvailability(fieldId, type) {
  const value = document.getElementById(fieldId).value.trim();
  if (!value) return;

  // For phone field, format the number
  let checkValue = value;
  if (fieldId === "phone") {
    checkValue = value.replace(/\D/g, "");
    if (checkValue.length === 9 && checkValue.startsWith("7")) {
      checkValue = "0" + checkValue;
    }
  }

  // For plate field, convert to uppercase
  if (fieldId === "plate") {
    checkValue = value.toUpperCase();
  }

  $.ajax({
    url: "check_rider.php",
    type: "POST",
    dataType: "json",
    data: { field: type, value: checkValue },
    success: function(response) {
      let statusSpan = document.getElementById(fieldId + "Status");
      if (!statusSpan) {
        // Create status span if it doesn't exist
        statusSpan = document.createElement('span');
        statusSpan.id = fieldId + "Status";
        statusSpan.className = "availability-status";
        statusSpan.style.marginLeft = "10px";
        statusSpan.style.fontSize = "0.9em";
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
document.getElementById("plate").addEventListener("blur", () => checkAvailability("plate", "Motorcycle_Plate_Number"));