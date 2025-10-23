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

// openLogin() removed — navbar now links directly to server-side login page


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

// You can add similar functions for riders and drivers later
function openRiderRegister() {
    let width = 500;
    let height = 1500;

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

function openDriverRegister() {
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
        "../drivers/driver.html", // path to your client registration file
        "ClientRegister",
        `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
    );
}

// Enhanced popup functionality
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

