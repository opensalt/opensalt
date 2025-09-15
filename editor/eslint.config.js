import js from '@eslint/js';
import vue from 'eslint-plugin-vue';

export default [
  js.configs.recommended,
  ...vue.configs['flat/essential'],
  {
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: 'module',
      globals: {
        browser: true,
        node: true
      }
    },
    rules: {
      'vue/multi-word-component-names': 'off',
    }
  },
  {
    ignores: ['dist/**', 'node_modules/**']
  }
];
