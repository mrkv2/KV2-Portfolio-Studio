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
			markChecklistPending();
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
		markChecklistPending();
  });

	function markChecklistPending() {
		$("#kv2ps-checklist-note")
			.text(kv2psAdmin.checklistPending)
			.addClass("is-pending");
	}

	function refreshChecklist() {
		var $body = $("#kv2ps-completeness-body");
		if (!$body.length || !$body.data("post-id")) {
			return;
		}

		$.post(kv2psAdmin.ajaxUrl, {
			action: "kv2ps_completeness_report",
			post_id: $body.data("post-id"),
			nonce: $body.data("nonce"),
		})
			.done(function (response) {
				if (response && response.success && response.data && response.data.html) {
					$body.html(response.data.html);
					return;
				}
				$("#kv2ps-checklist-note").text(kv2psAdmin.checklistError);
			})
			.fail(function () {
				$("#kv2ps-checklist-note").text(kv2psAdmin.checklistError);
			});
	}

	$(document).on("click", "#kv2ps-refresh-checklist", function () {
		refreshChecklist();
	});

	$(document).on(
		"input change",
		"#poststuff input, #poststuff textarea, .editor-post-taxonomies__hierarchical-terms-list input, .components-form-token-field__input",
		function () {
			markChecklistPending();
		},
	);

	if (window.wp && wp.data && wp.data.select("core/editor")) {
		var wasSavingPost = false;
		wp.data.subscribe(function () {
			var editor = wp.data.select("core/editor");
			var isSavingPost =
				editor.isSavingPost() ||
				(typeof editor.isSavingMetaBoxes === "function" &&
					editor.isSavingMetaBoxes());
			if (wasSavingPost && !isSavingPost && editor.didPostSaveRequestSucceed()) {
				window.setTimeout(refreshChecklist, 800);
			}
			wasSavingPost = isSavingPost;
		});
	}

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
			markChecklistPending();
    },
  });

  $("#kv2ps-select-all").on("change", function () {
    $(".kv2ps-source-checkbox:not(:disabled)").prop("checked", this.checked);
  });

	$("#kv2ps-select-published").on("click", function () {
		$(".kv2ps-source-checkbox:not(:disabled)").each(function () {
			this.checked = $(this).data("source-status") === "publish";
		});
		$("#kv2ps-select-all").prop("checked", false);
	});

	$(document).on("click", ".kv2ps-use-destination-candidate", function () {
		$("#kv2ps-destination-url").val($(this).data("candidate") || "").trigger("change");
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
