import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: [
            'bootstrap/**',
            'node_modules/**',
            'public/**',
            'storage/**',
            'vendor/**',
        ],
    },
    {
        ...js.configs.recommended,
        files: ['resources/js/**/*.js', 'tests/e2e/**/*.js'],
        languageOptions: {
            ...js.configs.recommended.languageOptions,
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        rules: {
            'no-console': 'off',
        },
    },
    ...pluginVue.configs['flat/essential'].map((config) => ({
        ...config,
        files: ['resources/js/**/*.vue'],
        languageOptions: {
            ...config.languageOptions,
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
        rules: {
            ...(config.rules ?? {}),
            'no-console': 'off',
            'vue/multi-word-component-names': 'off',
        },
    })),
];
