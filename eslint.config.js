import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

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
    files: ['resources/js/**/*.vue', 'resources/js/**/*.js'],
    rules: {
      'vue/multi-word-component-names': 'off',
      'no-unused-vars': 'off',
      'no-undef': 'off',
    },
  },
];
