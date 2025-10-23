// Hero text zoom effect
const heroText = document.getElementById("hero-text");
let zoomIn = true;

setInterval(() => {
  if (zoomIn) {
    heroText.style.transform = "scale(1.07)";
  } else {
    heroText.style.transform = "scale(1)";
  }
  zoomIn = !zoomIn;
}, 2000);

// Handle newsletter subscription
document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector(".newsletter-form");
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = form.querySelector("input").value;
    alert(`Thank you for subscribing, ${email}!`);
    form.reset();
  });
});

function openLogin() {
  let width = 450;
  let height = 600;

  // Adjust size for smaller devices
  if (window.innerWidth < 576) {
    width = window.innerWidth - 40;
    height = window.innerHeight - 80;
  }

  const left = (screen.width - width) / 2;
  const top = (screen.height - height) / 2;

  // Pointing to the login folder page
  window.open(
    "../login/login.html",
    "LoginWindow",
    `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
  );

}


//open client registration
function openClientRegister() {
    let width = 500;
    let height = 700;

    // Adjust for small screens
    if (window.innerWidth < 576) {
        width = window.innerWidth - 40;
        height = window.innerHeight - 80;
    }

    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    window.open(
        "../clients/client.html", // path to your client registration file
        "ClientRegister",
        `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
    );
}

//open Rider registration
function openRiderRegister() {
    let width = 500;
    let height = 700;

    // Adjust for small screens
    if (window.innerWidth < 576) {
        width = window.innerWidth - 40;
        height = window.innerHeight - 80;
    }

    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    window.open(
        "../riders/rider.html", // path to your client registration file
        "RiderRegister",
        `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
    );

}

//open Driver registration
function openDriverRegister() {
    let width = 500;
    let height = 900;

    // Adjust for small screens
    if (window.innerWidth < 576) {
        width = window.innerWidth - 40;
        height = window.innerHeight - 80;
    }

    const left = (screen.width - width) / 2;
    const top = (screen.height - height) / 2;

    window.open(
        "../drivers/driver.html", // path to your client registration file
        "ClientRegister",
        `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
    );
}

// Enhanced popup functionality for the QUICK LINKS in the footer
document.addEventListener('DOMContentLoaded', function() {
    // Close popup when clicking close button
    document.querySelectorAll('.popup-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const popup = this.closest('.popup-content');
            popup.style.opacity = '0';
            popup.style.visibility = 'hidden';
        });
    });

    // Close popup when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.footer-link-item')) {
            document.querySelectorAll('.popup-content').forEach(popup => {
                popup.style.opacity = '0';
                popup.style.visibility = 'hidden';
            });
        }
    });

    // Mobile touch support
    let touchTimer;
    document.querySelectorAll('.footer-link').forEach(link => {
        link.addEventListener('touchstart', function(e) {
            e.preventDefault();
            const popup = this.nextElementSibling;
            
            // Close other popups
            document.querySelectorAll('.popup-content').forEach(p => {
                if (p !== popup) {
                    p.style.opacity = '0';
                    p.style.visibility = 'hidden';
                }
            });
            
            // Toggle current popup
            if (popup.style.visibility === 'visible') {
                popup.style.opacity = '0';
                popup.style.visibility = 'hidden';
            } else {
                popup.style.opacity = '1';
                popup.style.visibility = 'visible';
            }
        });
    });

    // Keyboard accessibility
    document.querySelectorAll('.footer-link').forEach(link => {
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const popup = this.nextElementSibling;
                
                document.querySelectorAll('.popup-content').forEach(p => {
                    if (p !== popup) {
                        p.style.opacity = '0';
                        p.style.visibility = 'hidden';
                    }
                });
                
                popup.style.opacity = '1';
                popup.style.visibility = 'visible';
            }
        });
    });
});



