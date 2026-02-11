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

          document
            .querySelectorAll(
              ".media-drop-info-wrapper, .media-light-table-info-wrapper",
            )
            .forEach(function (w) {
              if (w !== wrapper) {
                w.style.zIndex = "";
                const p = w.querySelector(
                  ".media-drop-info-popup, .media-light-table-info-popup",
                );
                if (p) p.style.display = "none";
              }
            });

          if (popup.style.display === "block") {
            popup.style.display = "none";
            wrapper.style.zIndex = "";
          } else {
            popup.style.display = "block";
            wrapper.style.zIndex = "1000";
          }
        });

        popup.addEventListener("click", function () {
          popup.style.display = "none";
        });
      });

      // Fermer tous les popups si on clique ailleurs
      document.addEventListener("click", function (e) {
        const isVBOElement = e.target.closest(
          ".vbo-select-all, .form-checkbox",
        );
        if (
          !isVBOElement &&
          !e.target.closest(
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
            });
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);
