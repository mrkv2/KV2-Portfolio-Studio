(function (wp, settings) {
  "use strict";

  if (
    !wp ||
    !settings ||
    !wp.components ||
    !wp.data ||
    !wp.element ||
    !wp.hooks
  ) {
    return;
  }

  var createElement = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useEffect = wp.element.useEffect;
  var useMemo = wp.element.useMemo;
  var useState = wp.element.useState;
  var FormTokenField = wp.components.FormTokenField;
  var Notice = wp.components.Notice;
  var Spinner = wp.components.Spinner;
  var decodeEntities =
    wp.htmlEntities && typeof wp.htmlEntities.decodeEntities === "function"
      ? wp.htmlEntities.decodeEntities
      : function (value) {
          return value;
        };

  function cleanName(value) {
    if (typeof value !== "string") {
      return "";
    }

    return String(decodeEntities(value)).trim();
  }

  function comparableName(value) {
    var name = cleanName(value);

    return typeof name.normalize === "function"
      ? name.normalize("NFKC").toLocaleLowerCase()
      : name.toLocaleLowerCase();
  }

  function sanitizeTerms(items) {
    var ids = {};

    return (Array.isArray(items) ? items : [])
      .map(function (term) {
        var id = term && Number.parseInt(term.id, 10);
        var name = term ? cleanName(term.name) : "";

        if (!id || !name || ids[id]) {
          return null;
        }

        ids[id] = true;
        return { id: id, name: name };
      })
      .filter(Boolean);
  }

  function mergeTerms(existingTerms, newTerms) {
    return sanitizeTerms((existingTerms || []).concat(newTerms || [])).sort(
      function (termA, termB) {
        return termA.name.localeCompare(termB.name, undefined, {
          sensitivity: "base",
        });
      },
    );
  }

  function uniqueNames(names) {
    var seen = {};

    return (Array.isArray(names) ? names : [])
      .map(cleanName)
      .filter(function (name) {
        var key = comparableName(name);

        if (!key || seen[key]) {
          return false;
        }

        seen[key] = true;
        return true;
      });
  }

  function findTermByName(terms, name) {
    var key = comparableName(name);

    return (terms || []).find(function (term) {
      return comparableName(term.name) === key;
    });
  }

  function requestCreatedTerm(name) {
    var request = new window.URLSearchParams();

    request.append("action", "kv2ps_create_city");
    request.append("nonce", settings.nonce || "");
    request.append("name", name);

    return window
      .fetch(settings.ajaxUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: request.toString(),
      })
      .then(function (response) {
        return response.json();
      })
      .then(function (response) {
        var sanitized = sanitizeTerms(
          response && response.success && response.data
            ? [response.data.term]
            : [],
        );

        if (!sanitized.length) {
          throw new Error(
            response && response.data && response.data.message
              ? response.data.message
              : settings.saveError,
          );
        }

        return sanitized[0];
      });
  }

  if (settings.testMode) {
    window.kv2psCitySelectorTest = {
      cleanName: cleanName,
      comparableName: comparableName,
      sanitizeTerms: sanitizeTerms,
      mergeTerms: mergeTerms,
      uniqueNames: uniqueNames,
      findTermByName: findTermByName,
      requestCreatedTerm: requestCreatedTerm,
    };
  }

  function CitySelector(props) {
    var selection = wp.data.useSelect(
      function (select) {
        var core = select("core");
        var editor = select("core/editor");
        var taxonomy = core
          ? core.getEntityRecord("root", "taxonomy", props.slug)
          : null;
        var restBase = taxonomy && taxonomy.rest_base;
        var selectedIds =
          editor && restBase
            ? editor.getEditedPostAttribute(restBase) || []
            : [];

        return {
          taxonomy: taxonomy,
          selectedIds: Array.isArray(selectedIds)
            ? selectedIds.map(Number).filter(Boolean)
            : [],
        };
      },
      [props.slug],
    );
    var restBase =
      selection.taxonomy && selection.taxonomy.rest_base
        ? selection.taxonomy.rest_base
        : props.slug;
    var selectedKey = selection.selectedIds.join(",");
    var termsState = useState(mergeTerms([], settings.terms));
    var terms = termsState[0];
    var setTerms = termsState[1];
    var valuesState = useState([]);
    var values = valuesState[0];
    var setValues = valuesState[1];
    var savingState = useState(false);
    var saving = savingState[0];
    var setSaving = savingState[1];
    var errorState = useState(cleanName(settings.termsError));
    var error = errorState[0];
    var setError = errorState[1];
    useEffect(
      function () {
        if (saving) {
          return;
        }

        setValues(
          selection.selectedIds
            .map(function (id) {
              return terms.find(function (term) {
                return term.id === id;
              });
            })
            .filter(Boolean)
            .map(function (term) {
              return term.name;
            }),
        );
      },
      [terms, selectedKey, saving],
    );

    var suggestions = useMemo(
      function () {
        return terms
          .map(function (term) {
            return cleanName(term.name);
          })
          .filter(function (name) {
            return typeof name === "string" && name.length > 0;
          });
      },
      [terms],
    );

    function canCreateTerms() {
      return settings.canCreate === true || settings.canCreate === "1";
    }

    function updatePostTerms(ids) {
      var edit = {};

      edit[restBase] = ids;
      wp.data.dispatch("core/editor").editPost(edit);
    }

    function createTerm(name) {
      return requestCreatedTerm(name);
    }

    function selectedNames() {
      return selection.selectedIds
        .map(function (id) {
          return terms.find(function (term) {
            return term.id === id;
          });
        })
        .filter(Boolean)
        .map(function (term) {
          return term.name;
        });
    }

    function onChange(nextValues) {
      var names = uniqueNames(nextValues);
      var existingIds = [];
      var missingNames = [];

      setValues(names);
      setError("");

      names.forEach(function (name) {
        var term = findTermByName(terms, name);

        if (term) {
          existingIds.push(term.id);
        } else {
          missingNames.push(name);
        }
      });

      if (!missingNames.length) {
        updatePostTerms(existingIds);
        return;
      }

      if (!canCreateTerms()) {
        setError(settings.createDenied);
        setValues(selectedNames());
        return;
      }

      setSaving(true);
      Promise.all(missingNames.map(createTerm))
        .then(function (createdTerms) {
          var allTerms = mergeTerms(terms, createdTerms);
          var finalIds = names
            .map(function (name) {
              var term = findTermByName(allTerms, name);

              return term ? term.id : 0;
            })
            .filter(Boolean);

          setTerms(allTerms);
          updatePostTerms(finalIds);
        })
        .catch(function (requestError) {
          setValues(selectedNames());
          setError(
            requestError && requestError.message
              ? requestError.message
              : settings.saveError,
          );
        })
        .finally(function () {
          setSaving(false);
        });
    }

    if (!selection.taxonomy) {
      return createElement(Spinner);
    }

    return createElement(
      Fragment,
      null,
      error
        ? createElement(
            Notice,
            { status: "error", isDismissible: false },
            error,
          )
        : null,
      !settings.termsError
        ? createElement(FormTokenField, {
            value: values,
            suggestions: suggestions,
            onChange: onChange,
            disabled: saving,
            label: settings.label,
            placeholder: settings.placeholder,
            help: settings.help,
            maxSuggestions: 100,
            messages: {
              added: settings.added,
              removed: settings.removed,
              remove: settings.remove,
              __experimentalInvalid: settings.invalid,
            },
          })
        : null,
      saving
        ? createElement(
            "p",
            { className: "description kv2ps-city-selector__saving" },
            settings.saving,
          )
        : null,
    );
  }

  wp.hooks.addFilter(
    "editor.PostTaxonomyType",
    "kv2-portfolio-studio/city-selector",
    function (OriginalComponent) {
      return function KV2PSTaxonomySelector(props) {
        if (props.slug === settings.taxonomy) {
          return createElement(CitySelector, props);
        }

        return createElement(OriginalComponent, props);
      };
    },
  );
})(window.wp, window.kv2psCitySelector);
