(function ($, Drupal, drupalSettings, once) {
  "use strict";

  Drupal.behaviors.popupFlexGrid = {
    attach: function (context, settings) {
      function positionInfoPopup(popup, wrapper) {
        const padding = 8;

        popup.style.position = "fixed";
        popup.style.display = "block";
        popup.style.visibility = "hidden";
        popup.style.transform = "none";
        popup.style.zIndex = "99999";

        // Mobile-friendly width/height constraints.
        const maxWidth = Math.min(560, window.innerWidth - padding * 2);
        popup.style.width = "min(" + maxWidth + "px, calc(100vw - " + (padding * 2) + "px))";
        popup.style.maxWidth = maxWidth + "px";
        popup.style.maxHeight = Math.floor(window.innerHeight * 0.75) + "px";
        popup.style.overflowY = "auto";

        const popupRect = popup.getBoundingClientRect();
        const wrapperRect = wrapper.getBoundingClientRect();

        let left = wrapperRect.left + (wrapperRect.width / 2) - (popupRect.width / 2);
        left = Math.max(padding, Math.min(left, window.innerWidth - popupRect.width - padding));

        let top = wrapperRect.bottom + 6;
        if (top + popupRect.height > window.innerHeight - padding) {
          top = wrapperRect.top - popupRect.height - 6;
        }
        if (top < padding) {
          top = padding;
        }

        popup.style.left = left + "px";
        popup.style.top = top + "px";
        popup.style.visibility = "visible";
      }

      // ========================================
      // POPUP - Gestion du menu "Plus..."
      // ========================================
      once(
        "entity-popup3",
        ".media-drop-info-wrapper, .media-light-table-info-wrapper",
        context,
      ).forEach(function (wrapper) {
        const button = wrapper.querySelector(
          ".media-drop-info-button, .media-light-table-info-button",
        );
        const popup = wrapper.querySelector(
          ".media-drop-info-popup, .media-light-table-info-popup",
        );

        if (!button || !popup) return;

        wrapper.addEventListener("mousedown", function (e) {
          e.stopPropagation();
        });

        button.addEventListener("click", function (e) {
          e.stopPropagation();

          // Fermer les autres popups ouverts avec nettoyage complet
          document
            .querySelectorAll(
              ".media-drop-info-wrapper, .media-light-table-info-wrapper",
            )
            .forEach(function (w) {
              if (w !== wrapper) {
                w.style.zIndex = "";
                // Chercher le popup d'abord dans le wrapper, puis au niveau du body
                let p = w.querySelector(
                  ".media-drop-info-popup, .media-light-table-info-popup",
                );

                // Si pas trouve dans le wrapper, chercher au body avec un attribut data-wrapper
                if (!p) {
                  // Parcourir tous les popups au body pour trouver celui associé à ce wrapper
                  const allPopups = document.querySelectorAll(
                    ".media-drop-info-popup, .media-light-table-info-popup",
                  );
                  for (const popup of allPopups) {
                    if (popup.style.display === "block" && popup.parentElement === document.body) {
                      // Vérifier si ce popup était originallement dans ce wrapper
                      const originalWrapper = popup._originalWrapper;
                      if (originalWrapper === w) {
                        p = popup;
                        break;
                      }
                    }
                  }
                }

                if (p && p.style.display === "block") {
                  p.style.display = "none";
                  p.style.position = "";
                  p.style.top = "";
                  p.style.left = "";
                  p.style.transform = "";
                  p.style.zIndex = "";
                  p.style.visibility = "";
                  p.style.maxWidth = "";
                  p.style.overflowY = "";
                  p.style.maxHeight = "";
                  // Remettre le popup dans son conteneur d'origine
                  if (p.parentElement === document.body) {
                    w.appendChild(p);
                    delete p._originalWrapper;
                  }
                }
              }
            });

          if (popup.style.display === "block") {
            popup.style.display = "none";
            wrapper.style.zIndex = "";
            popup.style.position = "";
            popup.style.top = "";
            popup.style.left = "";
            popup.style.transform = "";
            popup.style.zIndex = "";
            popup.style.visibility = "";
            popup.style.maxWidth = "";
            popup.style.overflowY = "";
            popup.style.maxHeight = "";
            // Remettre le popup dans son conteneur d'origine
            if (popup.parentElement === document.body) {
              wrapper.appendChild(popup);
              delete popup._originalWrapper;
            }
          } else {
            // For light table wrappers, load popup content through AJAX callback
            // instead of embedding sensitive values in HTML.
            if (wrapper.classList.contains("media-light-table-info-wrapper")) {
              const mediaId = parseInt(wrapper.getAttribute("data-media-id") || "0", 10);
              const isLoaded = wrapper.getAttribute("data-popup-loaded") === "1";

              if (mediaId > 0 && !isLoaded) {
                popup.innerHTML =
                  '<span class="media-light-table-popup-loading">' +
                  Drupal.t("Loading...") +
                  "</span>";

                let callbackBase = "/media-light-table/media-info/";
                if (
                  drupalSettings &&
                  drupalSettings.dragtool &&
                  drupalSettings.dragtool.lightTable &&
                  drupalSettings.dragtool.lightTable.mediaInfoCallback
                ) {
                  callbackBase = drupalSettings.dragtool.lightTable.mediaInfoCallback;
                }

                fetch(callbackBase + mediaId, { credentials: "same-origin" })
                  .then(function (response) {
                    if (!response.ok) {
                      throw new Error("HTTP " + response.status);
                    }
                    return response.json();
                  })
                  .then(function (info) {
                    const sizeMb = info.size_bytes > 0
                      ? (info.size_bytes / 1024 / 1024).toFixed(2) + " MB"
                      : "";

                    let html =
                      '<span class="media-light-table-image-id media-light-table-popup-field">' +
                      "<strong>" + Drupal.t("ID") + ":</strong> " + (info.id || "") +
                      "</span>";

                    if (info.file_name) {
                      html +=
                        '<span class="media-light-table-image-name media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("Name") + ":</strong> " + info.file_name +
                        "</span>";
                    }
                    if (info.file_path) {
                      html +=
                        '<span class="media-light-table-image-filepath media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("Path") + ":</strong> " + info.file_path +
                        "</span>";
                    }
                    if (sizeMb) {
                      html +=
                        '<span class="media-light-table-image-size media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("Size") + ":</strong> " + sizeMb +
                        "</span>";
                    }
                    if (info.mime_type) {
                      html +=
                        '<span class="media-light-table-image-mime-type media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("MIME") + ":</strong> " + info.mime_type +
                        "</span>";
                    }
                    if (info.width > 0 && info.height > 0) {
                      html +=
                        '<span class="media-light-table-image-dimensions media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("Dimensions") + ":</strong> " + info.width + "x" + info.height +
                        "</span>";
                    }
                    if (info.bundle) {
                      html +=
                        '<span class="media-light-table-image-bundle media-light-table-popup-field">' +
                        "<strong>" + Drupal.t("Media Type") + ":</strong> " + info.bundle +
                        "</span>";
                    }

                    if (info.custom_fields) {
                      Object.keys(info.custom_fields).forEach(function (fieldName) {
                        const row = info.custom_fields[fieldName] || {};
                        const label = row.label || fieldName;
                        const value = row.value || "";
                        if (value !== "") {
                          html +=
                            '<span class="media-light-table-image-field media-light-table-popup-field">' +
                            "<strong>" + label + ":</strong> " + value +
                            "</span>";
                        }
                      });
                    }

                    popup.innerHTML = html;
                    wrapper.setAttribute("data-popup-loaded", "1");
                  })
                  .catch(function () {
                    popup.innerHTML =
                      '<span class="media-light-table-popup-error">' +
                      Drupal.t("Error loading data.") +
                      "</span>";
                  });
              }
            }

            popup.style.display = "block";
            wrapper.style.zIndex = "1000";

            // Reparenter le popup au groupe principal pour qu'il dépasse tous les wrappers
            const groupWrapper = wrapper.closest(".media-light-table-group-wrapper");
            if (groupWrapper) {
              // Ajouter a document.body pour éviter les contextes de stacking
              if (popup.parentElement !== document.body) {
                // Mémoriser le wrapper d'origine pour retrouver le popup plus tard
                popup._originalWrapper = wrapper;
                document.body.appendChild(popup);
              }

              positionInfoPopup(popup, wrapper);
            }
          }
        });

        popup.addEventListener("click", function () {
          popup.style.display = "none";
          popup.style.position = "";
          popup.style.top = "";
          popup.style.left = "";
          popup.style.transform = "";
          popup.style.zIndex = "";
          popup.style.visibility = "";
          popup.style.maxWidth = "";
          popup.style.overflowY = "";
          popup.style.maxHeight = "";
          // Remettre le popup dans son conteneur d'origine
          if (popup.parentElement === document.body) {
            wrapper.appendChild(popup);
            delete popup._originalWrapper;
          }
        });
      });

      // Gérer le scroll et la fermeture des popups déplacés
      window.addEventListener("scroll", function () {
        document
          .querySelectorAll(
            ".media-drop-info-popup, .media-light-table-info-popup",
          )
          .forEach(function (p) {
            if (p.style.display === "block" && p.parentElement === document.body) {
              // Mettre à jour la position du popup au scroll
              const wrapper = p._originalWrapper;
              if (wrapper) {
                positionInfoPopup(p, wrapper);
              }
            }
          });
      });

      window.addEventListener("resize", function () {
        document
          .querySelectorAll(
            ".media-drop-info-popup, .media-light-table-info-popup",
          )
          .forEach(function (p) {
            if (p.style.display === "block" && p.parentElement === document.body) {
              const wrapper = p._originalWrapper;
              if (wrapper) {
                positionInfoPopup(p, wrapper);
              }
            }
          });
      });

      // Fermer tous les popups si on clique ailleurs
      document.addEventListener("click", function (e) {
        if (!e.target.closest(
            ".media-drop-info-wrapper, .media-light-table-info-wrapper",
          ) &&
          !e.target.closest(".dropbutton-wrapper")
        ) {
          document
            .querySelectorAll(
              ".media-drop-info-popup, .media-light-table-info-popup",
            )
            .forEach(function (p) {
              p.style.display = "none";
              p.style.position = "";
              p.style.top = "";
              p.style.left = "";
              p.style.transform = "";
              p.style.zIndex = "";
              p.style.visibility = "";
              p.style.maxWidth = "";
              p.style.overflowY = "";
              p.style.maxHeight = "";
              // Remettre les popups déplacés dans leur conteneur d'origine
              if (p.parentElement === document.body) {
                // Chercher le wrapper d'origine stocké
                const originalWrapper = p._originalWrapper;
                if (originalWrapper) {
                  originalWrapper.appendChild(p);
                  delete p._originalWrapper;
                }
              }
            });
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);
