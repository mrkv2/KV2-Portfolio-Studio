(function ($) {
  "use strict";

  function syncIds($field) {
    var ids = $field
      .find(".kv2ps-emt-video-item")
      .map(function () {
        return $(this).attr("data-id");
      })
      .get();
    $field.find(".kv2ps-emt-video-ids").val(ids.join(",")).trigger("change");
  }

  function buildCard(item) {
    var title = item.title || item.filename || kv2psEtsMonToit.unknownTitle;
    var mime = item.mime || "video";
    var $card = $('<div class="kv2ps-emt-video-item">').attr("data-id", String(item.id));
    var $video = $("<video>").attr({
      src: item.url,
      controls: true,
      preload: "metadata",
      playsinline: "playsinline",
    });
    var $meta = $('<div class="kv2ps-emt-video-meta">').append(
      $("<strong>").text(title),
      $("<span>").text(mime),
    );
    var $remove = $('<button class="button-link-delete kv2ps-emt-remove-video" type="button">')
      .attr("aria-label", kv2psEtsMonToit.removeLabel)
      .text("×");

    return $card.append($video, $meta, $remove);
  }

  $(document).on("click", ".kv2ps-emt-select-videos", function (event) {
    event.preventDefault();
    var $field = $(this).closest(".kv2ps-emt-video-field");
    var frame = wp.media({
      title: kv2psEtsMonToit.mediaTitle,
      button: { text: kv2psEtsMonToit.mediaButton },
      library: { type: "video" },
      multiple: true,
    });

    frame.on("select", function () {
      var existing = $field
        .find(".kv2ps-emt-video-item")
        .map(function () {
          return String($(this).attr("data-id"));
        })
        .get();

      frame
        .state()
        .get("selection")
        .each(function (attachment) {
          var item = attachment.toJSON();
          var id = String(item.id);
          if (existing.indexOf(id) !== -1) {
            return;
          }
          existing.push(id);
          $field.find(".kv2ps-emt-video-preview").append(buildCard(item));
        });

      syncIds($field);
    });

    frame.open();
  });

  $(document).on("click", ".kv2ps-emt-remove-video", function () {
    var $field = $(this).closest(".kv2ps-emt-video-field");
    $(this).closest(".kv2ps-emt-video-item").remove();
    syncIds($field);
  });

  $(function () {
    $(".kv2ps-emt-video-preview").sortable({
      items: ".kv2ps-emt-video-item",
      handle: ".kv2ps-emt-video-meta",
      cursor: "move",
      update: function () {
        syncIds($(this).closest(".kv2ps-emt-video-field"));
      },
    });
  });
})(jQuery);
