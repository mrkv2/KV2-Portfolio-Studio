(function () {
  "use strict";

  document.documentElement.classList.add("kv2ps-js");

  function normalize(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function layoutMasonry(grid) {
    if (!grid || !grid.classList.contains("kv2ps-layout-masonry")) {
      return;
    }

    grid.querySelectorAll("img[sizes]").forEach(function (image) {
      image.setAttribute(
        "sizes",
        image.getAttribute("sizes").replace(/^auto,\s*/i, ""),
      );
    });

    window.cancelAnimationFrame(grid.kv2psLayoutFrame || 0);
    grid.kv2psLayoutFrame = window.requestAnimationFrame(function () {
      var cards = Array.prototype.filter.call(
        grid.querySelectorAll(":scope > .kv2ps-card"),
        function (card) {
          return !card.hidden;
        },
      );
      var requested = [1, 2, 3, 4].find(function (count) {
        return grid.classList.contains("kv2ps-cols-" + count);
      }) || 1;
      var columns = window.innerWidth <= 620 ? 1 : window.innerWidth <= 900 ? Math.min(2, requested) : requested;
      var gap = 40;
      var width = (grid.clientWidth - gap * (columns - 1)) / columns;
      var heights = Array(columns).fill(0);

      grid.classList.add("kv2ps-masonry-ready");
      cards.forEach(function (card) {
        card.style.width = width + "px";
      });
      cards.forEach(function (card) {
        var column = heights.indexOf(Math.min.apply(Math, heights));
        card.style.left = column * (width + gap) + "px";
        card.style.top = heights[column] + "px";
        heights[column] += card.offsetHeight + 50;
      });
      grid.style.height = cards.length
        ? Math.max.apply(Math, heights) - 50 + "px"
        : "0px";
    });
  }

  function watchCardImages(grid, cards) {
    Array.prototype.forEach.call(cards, function (card) {
      card.querySelectorAll("img").forEach(function (image) {
        if (image.dataset.kv2psLayoutReady) {
          return;
        }
        image.dataset.kv2psLayoutReady = "1";
        if (!image.complete) {
          image.addEventListener("load", function () {
            layoutMasonry(grid);
          });
          image.addEventListener("error", function () {
            layoutMasonry(grid);
          });
        }
      });
    });
  }

  function applyCollectionFilters(collection) {
    var toolbar = collection.querySelector(".kv2ps-toolbar");
    var grid = collection.querySelector(".kv2ps-grid");
    if (!toolbar || !grid) {
      layoutMasonry(grid);
      return;
    }

    var service = toolbar.dataset.activeFilter || "";
    var input = toolbar.querySelector("input[type='search']");
    var search = normalize(input ? input.value : "");
    var visible = 0;
    var cards = grid.querySelectorAll(":scope > .kv2ps-card");

    cards.forEach(function (card) {
      var services = (card.dataset.kv2psServices || "").split(/\s+/);
      var matchesService = !service || services.indexOf(service) !== -1;
      var matchesSearch = !search || normalize(card.dataset.kv2psSearch).indexOf(search) !== -1;
      card.hidden = !(matchesService && matchesSearch);
      if (!card.hidden) {
        visible += 1;
      }
    });

    var status = toolbar.querySelector(".kv2ps-filter-status");
    if (status) {
      status.textContent = visible
        ? visible + " réalisation(s) correspondante(s) parmi les éléments chargés."
        : "Aucune réalisation correspondante parmi les éléments chargés.";
    }
    layoutMasonry(grid);
  }

  function initializeToolbar(collection) {
    var toolbar = collection.querySelector(".kv2ps-toolbar");
    if (!toolbar || toolbar.dataset.kv2psReady) {
      return;
    }
    toolbar.dataset.kv2psReady = "1";

    toolbar.querySelectorAll("[data-kv2ps-filter]").forEach(function (filter) {
      filter.addEventListener("click", function (event) {
        event.preventDefault();
        toolbar.dataset.activeFilter = filter.dataset.kv2psFilter || "";
        toolbar.querySelectorAll("[data-kv2ps-filter]").forEach(function (item) {
          var active = item === filter;
          item.classList.toggle("is-active", active);
          if (active) {
            item.setAttribute("aria-current", "page");
          } else {
            item.removeAttribute("aria-current");
          }
        });
        var hiddenService = toolbar.querySelector("input[name='kv2ps_service']");
        if (hiddenService) {
          hiddenService.value = toolbar.dataset.activeFilter;
        }
        applyCollectionFilters(collection);
      });
    });

    var search = toolbar.querySelector("input[type='search']");
    if (search) {
      search.addEventListener("input", function () {
        applyCollectionFilters(collection);
      });
    }
    var form = toolbar.querySelector("form[role='search']");
    if (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        applyCollectionFilters(collection);
      });
    }
  }

  function initializeComparison(root) {
    root.querySelectorAll(".kv2ps-before-after").forEach(function (comparison) {
      var range = comparison.querySelector("input[type='range']");
      if (!range || range.dataset.kv2psReady) {
        return;
      }
      range.dataset.kv2psReady = "1";
      range.addEventListener("input", function () {
        comparison.style.setProperty("--kv2ps-position", range.value + "%");
      });
    });
  }

  function findCollection(documentRoot, key) {
    return Array.prototype.find.call(
      documentRoot.querySelectorAll(".kv2ps-collection"),
      function (collection) {
        return collection.dataset.collectionKey === key;
      },
    );
  }

  function initializeCollection(collection) {
    var mode = collection.dataset.loadMode;
    var button = collection.querySelector(".kv2ps-load-more");
    var grid = collection.querySelector(".kv2ps-grid");
    var status = collection.querySelector(".kv2ps-load-status");
    var observer;

    if (!button || !grid || mode === "paged" || button.dataset.kv2psReady) {
      return;
    }

    button.dataset.kv2psReady = "1";

    function loadNextPage() {
      var nextUrl = button.dataset.nextUrl;
      if (!nextUrl || button.disabled) {
        return;
      }

      button.disabled = true;
      collection.setAttribute("aria-busy", "true");
      button.querySelector("span").textContent = "Chargement…";

      fetch(nextUrl, { credentials: "same-origin" })
        .then(function (response) {
          if (!response.ok) {
            throw new Error("HTTP " + response.status);
          }
          return response.text();
        })
        .then(function (html) {
          var parsed = new DOMParser().parseFromString(html, "text/html");
          var incoming = findCollection(
            parsed,
            collection.dataset.collectionKey,
          );
          if (!incoming) {
            throw new Error("Collection introuvable");
          }

          var cards = incoming.querySelectorAll(".kv2ps-grid > .kv2ps-card");
          var fragment = document.createDocumentFragment();
          cards.forEach(function (card) {
            card.classList.add("kv2ps-card--loaded");
            fragment.appendChild(card);
          });
          grid.appendChild(fragment);

          watchCardImages(grid, cards);
          applyCollectionFilters(collection);

          var incomingButton = incoming.querySelector(".kv2ps-load-more");
          if (incomingButton && incomingButton.dataset.nextUrl) {
            button.dataset.nextUrl = incomingButton.dataset.nextUrl;
            button.disabled = false;
            button.querySelector("span").textContent =
              "Voir plus de réalisations";
          } else {
            button.remove();
          }

          if (status) {
            status.textContent = cards.length
              ? cards.length + " réalisation(s) supplémentaire(s) affichée(s)."
              : "Toutes les réalisations sont affichées.";
          }
        })
        .catch(function () {
          button.disabled = false;
          button.querySelector("span").textContent = "Réessayer";
          if (status) {
            status.textContent =
              "Le chargement a échoué. Utilisez la pagination située juste au-dessus.";
          }
        })
        .finally(function () {
          collection.removeAttribute("aria-busy");
        });
    }

    button.addEventListener("click", loadNextPage);

    if (mode === "infinite" && "IntersectionObserver" in window) {
      observer = new IntersectionObserver(
        function (entries) {
          if (entries[0].isIntersecting) {
            loadNextPage();
          }
        },
        { rootMargin: "300px 0px" },
      );
      observer.observe(button);
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    initializeComparison(document);
    document.querySelectorAll(".kv2ps-collection").forEach(function (collection) {
      var grid = collection.querySelector(".kv2ps-grid");
      initializeToolbar(collection);
      initializeCollection(collection);
      if (grid) {
        watchCardImages(grid, grid.querySelectorAll(":scope > .kv2ps-card"));
        applyCollectionFilters(collection);
      }
    });
    window.addEventListener("resize", function () {
      document.querySelectorAll(".kv2ps-layout-masonry").forEach(layoutMasonry);
    });
  });
})();
