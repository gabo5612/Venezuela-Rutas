document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("darkModeToggle");
  const iconDark = document.getElementById("darkModeIcon");
  const iconLight = document.getElementById("lightModeIcon");
  

  function updateIcon() {
    const isDark = document.documentElement.classList.contains("dark");
    iconDark.style.display = isDark ? "none" : "block";
    iconLight.style.display = isDark ? "block" : "none";
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



(() => {
  const container = document.getElementById("postsContainer");
  const btn = document.getElementById("moreTips");

  if (!container || !btn || typeof TipsAjax === "undefined") return;

  let loading = false;

  async function loadMore() {
    if (loading) return;

    loading = true;
    btn.disabled = true;
    btn.textContent = "Loading...";

    const page = parseInt(btn.dataset.page || "2", 10);

    const formData = new FormData();
    formData.append("action", "load_more_tips");
    formData.append("nonce", TipsAjax.nonce);
    formData.append("page", String(page));
    formData.append("perPage", String(TipsAjax.perPage || 4));

    try {
      const res = await fetch(TipsAjax.ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: formData,
      });

      const data = await res.json();

      if (data.success) {
        container.insertAdjacentHTML("beforeend", data.data.html || "");

        if (!data.data.has_more) {
          btn.remove();
          return;
        }

        btn.dataset.page = String(page + 1);
      }
    } catch (e) {
      // silent fail
    } finally {
      loading = false;
      if (document.body.contains(btn)) {
        btn.disabled = false;
        btn.textContent = "Load More Tips";
      }
    }
  }

  btn.addEventListener("click", loadMore);
})();
