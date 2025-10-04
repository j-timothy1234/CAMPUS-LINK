// Small animation effect on scroll
document.addEventListener("DOMContentLoaded", () => {
  const serviceCards = document.querySelectorAll(".service-card");

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add("fade-in");
      }
    });
  }, { threshold: 0.2 });

  serviceCards.forEach(card => {
    observer.observe(card);
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




