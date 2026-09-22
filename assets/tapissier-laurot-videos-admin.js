(function ($) {
  "use strict";

  function syncIds($field) {
    var ids = $field
      .find(".kv2ps-laurot-video-item")
      .map(function () {
        return $(this).attr("data-id");
      })
      .get();
    $field.find(".kv2ps-laurot-video-ids").val(ids.join(",")).trigger("change");
  }

  function buildCard(item) {
    var title = item.title || item.filename || kv2psLaurotVideos.unknownTitle;
    var mime = item.mime || "video";
    var $card = $('<div class="kv2ps-laurot-video-item">').attr("data-id", String(item.id));
    var $video = $("<video>").attr({
      src: item.url,
      controls: true,
      preload: "metadata",
      playsinline: "playsinline",
    });
    var $meta = $('<div class="kv2ps-laurot-video-meta">').append(
      $("<strong>").text(title),
      $("<span>").text(mime),
    );
    var $remove = $('<button class="button-link-delete kv2ps-laurot-remove-video" type="button">')
      .attr("aria-label", kv2psLaurotVideos.removeLabel)
      .text("×");

    return $card.append($video, $meta, $remove);
  }

  $(document).on("click", ".kv2ps-laurot-select-videos", function (event) {
    event.preventDefault();
    var $field = $(this).closest(".kv2ps-laurot-video-field");
    var frame = wp.media({
      title: kv2psLaurotVideos.mediaTitle,
      button: { text: kv2psLaurotVideos.mediaButton },
      library: { type: "video" },
      multiple: true,
    });

    frame.on("select", function () {
      var existing = $field
        .find(".kv2ps-laurot-video-item")
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
          $field.find(".kv2ps-laurot-video-preview").append(buildCard(item));
        });

      syncIds($field);
    });

    frame.open();
  });

  $(document).on("click", ".kv2ps-laurot-remove-video", function () {
    var $field = $(this).closest(".kv2ps-laurot-video-field");
    $(this).closest(".kv2ps-laurot-video-item").remove();
    syncIds($field);
  });

  $(function () {
    $(".kv2ps-laurot-video-preview").sortable({
      items: ".kv2ps-laurot-video-item",
      handle: ".kv2ps-laurot-video-meta",
      cursor: "move",
      update: function () {
        syncIds($(this).closest(".kv2ps-laurot-video-field"));
      },
    });
  });
})(jQuery);
