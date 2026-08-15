import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import ts from 'typescript-eslint';

export default [
  {
    ignores: [
      'node_modules/**',
      'public/build/**',
      'vendor/**',
      'storage/**',
      'dist/**',
      '**/*.d.ts',
    ],
  },
  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['resources/js/**/*.ts'],
    languageOptions: {
      parser: ts.parser,
      sourceType: 'module',
    },
    rules: {
      'no-unused-vars': 'off',
      'no-undef': 'off',
    },
  },
  {
    files: ['resources/js/**/*.vue'],
    languageOptions: {
      parser: pluginVue.parser,
      parserOptions: {
        parser: ts.parser,
        sourceType: 'module',
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
      'no-unused-vars': 'off',
      'no-undef': 'off',
    },
  },
];
