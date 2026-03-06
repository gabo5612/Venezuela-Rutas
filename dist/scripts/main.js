// ── Unified gallery modal (images + videos, navigable) ──────
window.openGalleryModal = function (items, startIndex) {
  var current = startIndex || 0;
  var total   = items.length;

  var overlay = document.createElement('div');
  overlay.className = 'gal-modal';
  overlay.innerHTML =
    '<div class="gal-modal__stage">' +
      '<span class="gal-modal__counter"></span>' +
      '<button class="gal-modal__close" aria-label="Cerrar">' +
        '<span class="material-symbols-outlined">close</span>' +
      '</button>' +
      '<button class="gal-modal__nav gal-modal__nav--prev" aria-label="Anterior">' +
        '<span class="material-symbols-outlined">chevron_left</span>' +
      '</button>' +
      '<button class="gal-modal__nav gal-modal__nav--next" aria-label="Siguiente">' +
        '<span class="material-symbols-outlined">chevron_right</span>' +
      '</button>' +
    '</div>';

  var stage   = overlay.querySelector('.gal-modal__stage');
  var counter = overlay.querySelector('.gal-modal__counter');
  var btnPrev = overlay.querySelector('.gal-modal__nav--prev');
  var btnNext = overlay.querySelector('.gal-modal__nav--next');

  function render(idx) {
    current = (idx + total) % total;
    var el   = items[current];
    var type = el.dataset.type;
    var src  = el.dataset.src;

    // Remove previous media element
    var old = stage.querySelector('.gal-modal__media');
    if (old) {
      if (old.tagName === 'VIDEO') { old.pause(); old.src = ''; }
      old.remove();
    }

    var media;
    if (type === 'video') {
      media = document.createElement('video');
      media.src          = src;
      media.controls     = true;
      media.autoplay     = true;
      media.setAttribute('playsinline', '');
    } else {
      media = document.createElement('img');
      media.src = src;
      media.alt = '';
    }
    media.className = 'gal-modal__media';
    stage.appendChild(media);

    counter.textContent = (current + 1) + ' / ' + total;
    btnPrev.style.display = total > 1 ? '' : 'none';
    btnNext.style.display = total > 1 ? '' : 'none';
  }

  function close() {
    var v = stage.querySelector('video');
    if (v) { v.pause(); v.src = ''; }
    overlay.remove();
    document.body.style.overflow = '';
    document.removeEventListener('keydown', onKey);
  }

  function onKey(e) {
    if (e.key === 'Escape')       close();
    if (e.key === 'ArrowLeft')    render(current - 1);
    if (e.key === 'ArrowRight')   render(current + 1);
  }

  overlay.querySelector('.gal-modal__close').addEventListener('click', close);
  overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
  btnPrev.addEventListener('click', function (e) { e.stopPropagation(); render(current - 1); });
  btnNext.addEventListener('click', function (e) { e.stopPropagation(); render(current + 1); });
  document.addEventListener('keydown', onKey);

  document.body.appendChild(overlay);
  document.body.style.overflow = 'hidden';
  render(startIndex || 0);
};

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
