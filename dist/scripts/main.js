document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("darkModeToggle");
  const icon = document.getElementById("darkModeIcon");

  function updateIcon() {
    const isDark = document.documentElement.classList.contains("dark");
    icon.src = isDark ? icon.dataset.sun : icon.dataset.moon;
  }

  const savedTheme = localStorage.getItem("theme");

  if (savedTheme) {
    document.documentElement.classList.toggle("dark", savedTheme === "dark");
  } else {
    const systemDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    document.documentElement.classList.toggle("dark", systemDark);
  }

  updateIcon(); 
  toggleBtn.addEventListener("click", () => {
    const isDark = document.documentElement.classList.toggle("dark");
    localStorage.setItem("theme", isDark ? "dark" : "light");
    updateIcon(); 
  });
});
