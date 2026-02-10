
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menuToggle");
    const navMenu = document.getElementById("navMenu");
    const openIcon = menuToggle.querySelector(".open-menu");
    const closeIcon = menuToggle.querySelector(".close-menu");

    menuToggle.addEventListener("click", () => {
        navMenu.classList.toggle("show");

        const isOpen = navMenu.classList.contains("show");
        openIcon.style.display = isOpen ? "none" : "block";
        closeIcon.style.display = isOpen ? "block" : "none";
    });

    const menuLinks = navMenu.querySelectorAll("a");
    menuLinks.forEach(link => {
        link.addEventListener("click", () => {
            navMenu.classList.remove("show");
            openIcon.style.display = "block";
            closeIcon.style.display = "none";
        });
    });
});
