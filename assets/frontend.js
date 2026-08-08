(function () {
  "use strict";

  document.documentElement.classList.add("kv2ps-js");

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

  function refreshCollection(collection) {
    var toolbar = collection.querySelector(".kv2ps-toolbar");
    var grid = collection.querySelector(".kv2ps-grid");
    if (!grid) {
      layoutMasonry(grid);
      return;
    }

    var cards = grid.querySelectorAll(":scope > .kv2ps-card");
    cards.forEach(function (card) {
      card.hidden = false;
    });

    var status = toolbar
      ? toolbar.querySelector(".kv2ps-filter-status")
      : null;
    if (status) {
      status.textContent =
        cards.length + " réalisation(s) chargée(s) pour la sélection courante.";
    }
    layoutMasonry(grid);
  }

  function initializeToolbar(collection) {
    var toolbar = collection.querySelector(".kv2ps-toolbar");
    if (!toolbar || toolbar.dataset.kv2psReady) {
      return;
    }
    toolbar.dataset.kv2psReady = "1";

    /* Filters and search intentionally use their real URLs/forms. WordPress
     * therefore queries the complete portfolio instead of filtering only the
     * first batch already present in the DOM. This also keeps the controls
     * usable without JavaScript and lets the load-more URL retain the active
     * service/search parameters. */
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

  function initializeLightbox() {
    if (!("HTMLDialogElement" in window)) {
      return;
    }

    var dialog = document.createElement("dialog");
    dialog.className = "kv2ps-lightbox";
    dialog.setAttribute("aria-label", "Aperçu de la réalisation");
    dialog.innerHTML =
      '<button class="kv2ps-lightbox__close" type="button" aria-label="Fermer">×</button>' +
      '<button class="kv2ps-lightbox__nav kv2ps-lightbox__nav--previous" type="button" aria-label="Réalisation précédente">‹</button>' +
      '<figure><img alt=""><figcaption>' +
      '<span class="kv2ps-lightbox__count"></span>' +
      '<strong class="kv2ps-lightbox__title"></strong>' +
      '<span class="kv2ps-lightbox__meta"></span>' +
      "</figcaption></figure>" +
      '<button class="kv2ps-lightbox__nav kv2ps-lightbox__nav--next" type="button" aria-label="Réalisation suivante">›</button>' +
      '<p class="kv2ps-lightbox__status" aria-live="polite"></p>';
    document.body.appendChild(dialog);

    var image = dialog.querySelector("img");
    var count = dialog.querySelector(".kv2ps-lightbox__count");
    var title = dialog.querySelector(".kv2ps-lightbox__title");
    var meta = dialog.querySelector(".kv2ps-lightbox__meta");
    var status = dialog.querySelector(".kv2ps-lightbox__status");
    var close = dialog.querySelector(".kv2ps-lightbox__close");
    var previous = dialog.querySelector(".kv2ps-lightbox__nav--previous");
    var next = dialog.querySelector(".kv2ps-lightbox__nav--next");
    var trigger = null;
    var loading = false;
    var pointerStartX = null;

    function canonicalLink(link) {
      var card = link ? link.closest(".kv2ps-card") : null;
      return card
        ? card.querySelector(".kv2ps-card__image[data-kv2ps-lightbox]") || link
        : link;
    }

    function linksForCurrentCollection() {
      var collection = trigger ? trigger.closest(".kv2ps-collection") : null;
      var root = collection || document;
      return Array.prototype.slice.call(
        root.querySelectorAll(
          ".kv2ps-card__image[data-kv2ps-lightbox]",
        ),
      );
    }

    function updateNavigationState() {
      var links = linksForCurrentCollection();
      var current = links.indexOf(trigger);
      var collection = trigger ? trigger.closest(".kv2ps-collection") : null;
      var canLoadMore = !!(
        collection && collection.querySelector(".kv2ps-load-more")
      );
      count.textContent =
        current >= 0
          ? current + 1 + " / " + links.length + (canLoadMore ? "+" : "")
          : "";
      previous.disabled = loading || links.length < 2;
      next.disabled = loading || (links.length < 2 && !canLoadMore);
    }

    function showLink(link) {
      link = canonicalLink(link);
      if (!link) {
        return;
      }
      trigger = link;
      image.src = link.href;
      image.alt = link.dataset.kv2psLightboxTitle || "Réalisation";
      title.textContent = link.dataset.kv2psLightboxTitle || "Réalisation";
      meta.textContent = link.dataset.kv2psLightboxMeta || "";
      meta.hidden = !meta.textContent;
      status.textContent = "";
      updateNavigationState();
    }

    function setLoading(value) {
      loading = value;
      dialog.classList.toggle("is-loading", value);
      updateNavigationState();
    }

    function navigate(direction) {
      if (!trigger || loading) {
        return;
      }
      var links = linksForCurrentCollection();
      var current = links.indexOf(trigger);
      var target = current + direction;
      if (target >= 0 && target < links.length) {
        showLink(links[target]);
        return;
      }
      if (direction < 0 && links.length) {
        showLink(links[links.length - 1]);
        return;
      }

      var collection = trigger.closest(".kv2ps-collection");
      var loadMore = collection
        ? collection.querySelector(".kv2ps-load-more")
        : null;
      if (!loadMore) {
        if (links.length) {
          showLink(links[0]);
        }
        return;
      }

      setLoading(true);
      status.textContent = "Chargement des réalisations suivantes…";
      var loaded = function () {
        collection.removeEventListener("kv2ps:collection-error", failed);
        setLoading(false);
        var refreshed = linksForCurrentCollection();
        var refreshedIndex = refreshed.indexOf(trigger);
        if (refreshed[refreshedIndex + 1]) {
          showLink(refreshed[refreshedIndex + 1]);
        } else {
          status.textContent = "Toutes les réalisations sont affichées.";
        }
      };
      var failed = function () {
        collection.removeEventListener("kv2ps:collection-updated", loaded);
        setLoading(false);
        status.textContent =
          "Le chargement a échoué. Fermez la visionneuse puis utilisez Voir plus.";
      };
      collection.addEventListener("kv2ps:collection-updated", loaded, {
        once: true,
      });
      collection.addEventListener("kv2ps:collection-error", failed, {
        once: true,
      });
      loadMore.click();
    }

    close.addEventListener("click", function () {
      dialog.close();
    });
    previous.addEventListener("click", function () {
      navigate(-1);
    });
    next.addEventListener("click", function () {
      navigate(1);
    });
    dialog.addEventListener("click", function (event) {
      if (event.target === dialog) {
        dialog.close();
      }
    });
    dialog.addEventListener("keydown", function (event) {
      if (event.key === "ArrowLeft") {
        event.preventDefault();
        navigate(-1);
      } else if (event.key === "ArrowRight") {
        event.preventDefault();
        navigate(1);
      }
    });
    dialog.addEventListener("pointerdown", function (event) {
      pointerStartX = event.clientX;
    });
    dialog.addEventListener("pointerup", function (event) {
      if (pointerStartX === null) {
        return;
      }
      var distance = event.clientX - pointerStartX;
      pointerStartX = null;
      if (Math.abs(distance) < 60) {
        return;
      }
      navigate(distance > 0 ? -1 : 1);
    });
    dialog.addEventListener("close", function () {
      image.removeAttribute("src");
      if (trigger) {
        trigger.focus();
      }
    });
    document.addEventListener("click", function (event) {
      var link = event.target.closest("a[data-kv2ps-lightbox]");
      if (!link) {
        return;
      }
      event.preventDefault();
      showLink(link);
      dialog.showModal();
      next.focus();
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
          refreshCollection(collection);

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
          collection.dispatchEvent(
            new CustomEvent("kv2ps:collection-updated", {
              bubbles: true,
              detail: { added: cards.length },
            }),
          );
        })
        .catch(function () {
          button.disabled = false;
          button.querySelector("span").textContent = "Réessayer";
          if (status) {
            status.textContent =
              "Le chargement a échoué. Utilisez la pagination située juste au-dessus.";
          }
          collection.dispatchEvent(
            new CustomEvent("kv2ps:collection-error", { bubbles: true }),
          );
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
	initializeLightbox();
    initializeComparison(document);
    document.querySelectorAll(".kv2ps-collection").forEach(function (collection) {
      var grid = collection.querySelector(".kv2ps-grid");
      initializeToolbar(collection);
      initializeCollection(collection);
      if (grid) {
        watchCardImages(grid, grid.querySelectorAll(":scope > .kv2ps-card"));
        refreshCollection(collection);
      }
    });
    window.addEventListener("resize", function () {
      document.querySelectorAll(".kv2ps-layout-masonry").forEach(layoutMasonry);
    });
  });
})();
