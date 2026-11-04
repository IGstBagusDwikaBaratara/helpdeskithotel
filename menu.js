(function () {
  var h = document.documentElement;

  // Pulihkan pilihan terakhir secepat mungkin supaya tidak berkedip (hanya di layar lebar)
  try {
    if (localStorage.getItem("menu") === "1" && innerWidth > 860) h.classList.add("nav-open");
  } catch (e) {}

  document.addEventListener("DOMContentLoaded", function () {
    var b = document.getElementById("menu");
    var ov = document.getElementById("ov");
    if (!b || !ov) return;

    function set(buka) {
      h.classList.toggle("nav-open", buka);
      b.setAttribute("aria-expanded", String(buka));
      if (innerWidth > 860) {
        try { localStorage.setItem("menu", buka ? "1" : "0"); } catch (e) {}
      }
    }

    b.onclick = function () { set(!h.classList.contains("nav-open")); };
    ov.onclick = function () { set(false); };
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") set(false);
    });
    b.setAttribute("aria-expanded", String(h.classList.contains("nav-open")));
  });
})(
    
);