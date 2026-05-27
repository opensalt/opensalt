import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import vuejsAccessibility from 'eslint-plugin-vuejs-accessibility';
import globals from 'globals';

export default [
  {
    ignores: ['dist/**', 'node_modules/**', 'playwright-report/**', 'test-results/**']
  },
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.es2021,
        bootstrap: 'readonly',
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'vue/no-unused-vars': 'warn',
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
      'no-redeclare': 'off', // allow redundant global comments
      'no-useless-assignment': 'warn',
    }
  },
  {
    plugins: {
      'vuejs-accessibility': vuejsAccessibility,
    },
    rules: {
      'vuejs-accessibility/alt-text': 'error',
      'vuejs-accessibility/aria-props': 'error',
      'vuejs-accessibility/aria-role': 'error',
      'vuejs-accessibility/aria-unsupported-elements': 'error',
      'vuejs-accessibility/click-events-have-key-events': 'error',
      'vuejs-accessibility/form-control-has-label': 'warn',
      'vuejs-accessibility/interactive-supports-focus': 'error',
      'vuejs-accessibility/label-has-for': 'warn',
      'vuejs-accessibility/mouse-events-have-key-events': 'error',
      'vuejs-accessibility/no-access-key': 'error',
      'vuejs-accessibility/no-autofocus': 'error',
      'vuejs-accessibility/role-has-required-aria-props': 'error',
      'vuejs-accessibility/tabindex-no-positive': 'error',
    },
  },
  {
    files: ['tests/**/*.js', 'tests/**/*.test.js', 'tests/**/*.spec.js', 'tests/setup.js'],
    languageOptions: {
      globals: {
        ...globals.vitest,
      },
    },
  },
  {
    files: ['*.config.js'],
    languageOptions: {
      globals: {
        ...globals.node,
      },
    },
  },
];
