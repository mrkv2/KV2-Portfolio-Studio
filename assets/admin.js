(function ($) {
  "use strict";

  $(document).on("click", ".kv2ps-select-images", function (event) {
    event.preventDefault();
    var $field = $(this).closest(".kv2ps-gallery-field");
    var frame = wp.media({
      title: kv2psAdmin.mediaTitle,
      button: { text: kv2psAdmin.mediaButton },
      library: { type: "image" },
      multiple: true,
    });

    frame.on("select", function () {
      var current = $field
        .find(".kv2ps-gallery-ids")
        .val()
        .split(",")
        .filter(Boolean);
      frame
        .state()
        .get("selection")
        .each(function (attachment) {
          var item = attachment.toJSON();
          var id = String(item.id);
          if (current.indexOf(id) !== -1) {
            return;
          }
          current.push(id);
          var thumb =
            item.sizes && item.sizes.thumbnail
              ? item.sizes.thumbnail.url
              : item.url;
          $field.find(".kv2ps-gallery-preview").append(
            $('<div class="kv2ps-gallery-item">')
              .attr("data-id", id)
              .append(
                $("<img>").attr({ src: thumb, alt: "" }),
                $(
                  '<button class="button-link-delete kv2ps-remove-image" type="button" aria-label="Retirer l’image">×</button>',
                ),
              ),
          );
        });
      $field.find(".kv2ps-gallery-ids").val(current.join(","));
    });

    frame.open();
  });

  $(document).on("click", ".kv2ps-remove-image", function () {
    var $field = $(this).closest(".kv2ps-gallery-field");
    $(this).closest(".kv2ps-gallery-item").remove();
    var ids = $field
      .find(".kv2ps-gallery-item")
      .map(function () {
        return $(this).attr("data-id");
      })
      .get();
    $field.find(".kv2ps-gallery-ids").val(ids.join(","));
  });

  $(".kv2ps-gallery-preview").sortable({
    items: ".kv2ps-gallery-item",
    cursor: "move",
    update: function () {
      var $field = $(this).closest(".kv2ps-gallery-field");
      var ids = $field
        .find(".kv2ps-gallery-item")
        .map(function () {
          return $(this).attr("data-id");
        })
        .get();
      $field.find(".kv2ps-gallery-ids").val(ids.join(","));
    },
  });

  $("#kv2ps-select-all").on("change", function () {
    $(".kv2ps-source-checkbox:not(:disabled)").prop("checked", this.checked);
  });

  $(document).on("click", ".kv2ps-download-json", function () {
    var source = $(this)
      .closest(".kv2ps-output")
      .find(".kv2ps-download-source")
      .val();
    if (!source) {
      return;
    }
    var blob = new Blob([source], { type: "application/json;charset=utf-8" });
    var url = window.URL.createObjectURL(blob);
    var link = document.createElement("a");
    link.href = url;
    link.download = $(this).data("filename") || "kv2-export.json";
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  });
})(jQuery);
