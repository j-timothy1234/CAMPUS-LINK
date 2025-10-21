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

