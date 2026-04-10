const js = require("@eslint/js");
const globals = require("globals");

module.exports = [
  js.configs.recommended,
  {
    files: ["js/*.js"],
    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      globals: {
        ...globals.browser,
        Drupal: "readonly",
        drupalSettings: "readonly"
      }
    },
    rules: {
      "no-unused-vars": ["error", { "args": "none" }]
    }
  }
];
