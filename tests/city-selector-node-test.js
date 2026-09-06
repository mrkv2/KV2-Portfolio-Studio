const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

let registeredFilter = null;
const wp = {
  components: {
    FormTokenField: function FormTokenField() {},
    Notice: function Notice() {},
    Spinner: function Spinner() {},
  },
  data: {},
  element: {
    createElement: function (component, props) {
      return { component: component, props: props };
    },
    Fragment: "fragment",
    useEffect: function () {},
    useMemo: function (callback) {
      return callback();
    },
    useState: function (initialValue) {
      return [initialValue, function () {}];
    },
  },
  hooks: {
    addFilter: function (hookName, namespace, callback) {
      registeredFilter = {
        hookName: hookName,
        namespace: namespace,
        callback: callback,
      };
    },
  },
  htmlEntities: {
    decodeEntities: function (value) {
      return value;
    },
  },
};

global.window = {
  wp: wp,
  URLSearchParams: URLSearchParams,
  kv2psCitySelector: {
    taxonomy: "kv2_ville",
    testMode: true,
    terms: [
      { id: 1, name: "Paris" },
      undefined,
      { id: 4, name: "Paris 16" },
    ],
    ajaxUrl: "https://example.com/wp-admin/admin-ajax.php",
    nonce: "test-nonce",
    canCreate: true,
    saveError: "Creation failed",
  },
};

const source = fs.readFileSync(
  require("node:path").join(__dirname, "../assets/city-selector.js"),
  "utf8",
);
vm.runInThisContext(source, { filename: "city-selector.js" });

const utils = global.window.kv2psCitySelectorTest;
assert.ok(utils, "Test utilities must be available in test mode.");

const sanitized = utils.sanitizeTerms([
  { id: 1, name: "Paris" },
  undefined,
  { id: 2 },
  { id: 3, name: null },
  { id: "4", name: " Paris 16 " },
  { id: 4, name: "Duplicate id" },
]);
assert.deepEqual(sanitized, [
  { id: 1, name: "Paris" },
  { id: 4, name: "Paris 16" },
]);
assert.ok(
  sanitized.every(function (term) {
    return typeof term.name === "string" && term.name.length > 0;
  }),
  "Autocomplete must receive strings only.",
);

assert.deepEqual(utils.uniqueNames(["Paris", " paris ", "Paris 16", null]), [
  "Paris",
  "Paris 16",
]);
assert.equal(utils.findTermByName(sanitized, "PARIS 16").id, 4);

let fetchRequest = null;
global.window.fetch = function (url, options) {
  fetchRequest = { url: url, options: options };
  return Promise.resolve({
    json: function () {
      return Promise.resolve({
        success: true,
        data: { term: { id: 8, name: "Versailles" } },
      });
    },
  });
};

(async function () {
  const createdTerm = await utils.requestCreatedTerm("Versailles");
  assert.deepEqual(createdTerm, { id: 8, name: "Versailles" });
  assert.equal(
    fetchRequest.url,
    "https://example.com/wp-admin/admin-ajax.php",
  );
  const requestBody = new URLSearchParams(fetchRequest.options.body);
  assert.equal(requestBody.get("action"), "kv2ps_create_city");
  assert.equal(requestBody.get("nonce"), "test-nonce");
  assert.equal(requestBody.get("name"), "Versailles");
  assert.equal(registeredFilter.hookName, "editor.PostTaxonomyType");
  assert.equal(
    registeredFilter.namespace,
    "kv2-portfolio-studio/city-selector",
  );
  console.log("City selector JavaScript checks passed.");
})().catch(function (error) {
  console.error(error);
  process.exit(1);
});
