(function ($, Drupal, drupalSettings, once) {
  "use strict";

  Drupal.behaviors.popupFlexGrid = {
    attach: function (context, settings) {
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

              // Positionner temporairement pour mesurer les dimensions
              popup.style.position = "fixed";
              popup.style.display = "block";
              popup.style.visibility = "hidden";
              const popupRect = popup.getBoundingClientRect();

              // Calculer la position relative au wrapper
              const wrapperRect = wrapper.getBoundingClientRect();
              let left = wrapperRect.left + wrapperRect.width / 2;
              let top = wrapperRect.bottom + 5;

              const padding = 10; // Marge par rapport aux bords de l'écran
              const maxWidth = window.innerWidth - (padding * 2);

              // Vérifier et ajuster la largeur
              let popupWidth = popupRect.width;
              if (popupWidth > maxWidth) {
                popupWidth = maxWidth;
                popup.style.maxWidth = popupWidth + "px";
                popup.style.overflowY = "auto";
                popup.style.maxHeight = (window.innerHeight - padding * 2) + "px";
              }

              // Centrer horizontalement avec décalage (translateX(-50%))
              // Vérifier les bords gauche et droit
              const leftEdge = left - (popupWidth / 2);
              const rightEdge = left + (popupWidth / 2);

              if (leftEdge < padding) {
                left = padding + (popupWidth / 2);
              } else if (rightEdge > window.innerWidth - padding) {
                left = window.innerWidth - padding - (popupWidth / 2);
              }

              // Vérifier le bord inférieur et ajuster vers le haut si nécessaire
              if (top + popupRect.height > window.innerHeight - padding) {
                top = wrapperRect.top - popupRect.height - 5;
              }

              // Vérifier le bord supérieur
              if (top < padding) {
                top = padding;
              }

              // Appliquer les positions finales
              popup.style.visibility = "visible";
              popup.style.top = top + "px";
              popup.style.left = left + "px";
              popup.style.transform = "translateX(-50%)";
              popup.style.zIndex = "99999";
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
                const wrapperRect = wrapper.getBoundingClientRect();
                const popupRect = p.getBoundingClientRect();
                let left = wrapperRect.left + wrapperRect.width / 2;
                let top = wrapperRect.bottom + 5;

                const padding = 10;
                const popupWidth = p.offsetWidth || popupRect.width;

                // Vérifier les limites horizontales
                const leftEdge = left - (popupWidth / 2);
                const rightEdge = left + (popupWidth / 2);

                if (leftEdge < padding) {
                  left = padding + (popupWidth / 2);
                } else if (rightEdge > window.innerWidth - padding) {
                  left = window.innerWidth - padding - (popupWidth / 2);
                }

                // Vérifier le bord inférieur et remontrer si nécessaire
                if (top + popupRect.height > window.innerHeight - padding) {
                  top = wrapperRect.top - popupRect.height - 5;
                }

                // Vérifier le bord supérieur
                if (top < padding) {
                  top = padding;
                }

                p.style.top = top + "px";
                p.style.left = left + "px";
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
