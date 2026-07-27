module.exports = [
  // Migrate ignore patterns from `.eslintignore` here
  {
    ignores: ['node_modules', 'dist', '.envs'],
  },

  // Apply TypeScript parser and plugin for .ts files
  {
    files: ['**/*.ts'],
    languageOptions: {
      parser: require('@typescript-eslint/parser'),
      parserOptions: {
        project: './tsconfig.json',
        tsconfigRootDir: __dirname,
        ecmaVersion: 2023,
        sourceType: 'module',
      },
    },
    plugins: {
      '@typescript-eslint': require('@typescript-eslint/eslint-plugin'),
    },
    rules: {},
  },

  // Cypress-specific rules and plugin registration for tests
  {
    files: ['cypress/**/*.{js,ts}'],
    plugins: {
      cypress: require('eslint-plugin-cypress'),
    },
    // You can enable/disable specific cypress rules here. By default we load the plugin
    rules: {
      // keep plugin's rules available; leave defaults as-is, override only if needed
    },
  },

  // Basic JS handling
  {
    files: ['**/*.js'],
    languageOptions: {
      ecmaVersion: 2023,
      sourceType: 'module',
    },
    rules: {},
  },
]
