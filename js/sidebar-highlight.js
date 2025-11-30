// sidebar-highlight.js
document.addEventListener("DOMContentLoaded", () => {
  const path = window.location.pathname.split("/").pop(); // e.g., 'assessment.php'
  const links = document.querySelectorAll(".nav-link");

  links.forEach(link => {
    const page = link.getAttribute("data-page");
    if (page === path) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });
});
