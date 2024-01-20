const fs = require('fs');
const _ = require('lodash');

const strpkg = fs.readFileSync('package.json');

const pkg = JSON.parse(strpkg.toString());

const rules = {
    'max-len': [
        'error',
        {
            code: 120,
            ignoreRegExpLiterals: true,
            ignoreComments: true,
            ignoreStrings: true,
            ignoreTemplateLiterals: true,
        },
    ],
    'arrow-parens': ['error', 'always'],
    'class-methods-use-this': 1,
    eqeqeq: [2, 'smart'],
    'function-paren-newline': 0,
    'guard-for-in': 2,
    'import/no-extraneous-dependencies': 0,
    'import/order': 0,
    'import/extensions': 0,
    'lines-between-class-members': 0,
    'new-cap': 0,
    'newline-per-chained-call': 0,
    'no-alert': 1,
    'no-loops/no-loops': 1,
    'no-new': 1,
    'no-param-reassign': ['error', { props: false }],
    'no-undef': 2,
    'no-underscore-dangle': 0,
    'no-unused-vars': 1,
    'no-use-before-define': 0,
    'no-useless-computed-key': 0,
    'no-useless-constructor': 1,
    'node/no-missing-import': 0,
    'node/exports-style': ['error', 'module.exports'],
    'node/no-unsupported-features': 0,
    'node/no-unsupported-features/es-syntax': 0,
    'object-curly-newline': 0,
    'n/no-missing-import': 0,
    'n/no-extraneous-import': 0,
    'node/no-extraneous-import': 0,
    'optimize-regex/optimize-regex': 'warn',
    'prefer-destructuring': [
        'error',
        {
            VariableDeclarator: { array: false, object: true },
            AssignmentExpression: { array: true, object: false },
        },
        { enforceForRenamedProperties: false },
    ],
    'promise/avoid-new': 'off',
    'security/detect-object-injection': 0,
    'xss/no-location-href-assign': 2,
    'xss/no-mixed-html': 2,
    camelcase: 0,
    quotes: ['error', 'single'],
    strict: 1,
};

module.exports = _.merge(
    {
        plugins: [
            'no-jquery',
            'editorconfig',
            'eslint-comments',
            'import',
            'json',
            'lodash',
            'no-inferred-method-name',
            'no-loops',
            'node',
            'optimize-regex',
            'promise',
            'security',
            'xss',
        ],
        extends: [
            'airbnb-base',
            'plugin:no-jquery/deprecated',
            'plugin:eslint-comments/recommended',
            'plugin:import/errors',
            'plugin:import/warnings',
            'plugin:node/recommended',
            'plugin:promise/recommended',
            'plugin:security/recommended',
            'plugin:editorconfig/noconflict',
            'prettier',
            'stylelint',
        ],
        env: {
            es6: true,
            browser: true,
            node: true,
            mocha: true,
        },
        globals: {
            route: true,
            jQuery: true,
            $: true,
        },
        parserOptions: {
            parser: '@babel/eslint-parser',
            ecmaVersion: 2018,
            sourceType: 'module',
            jsx: false,
            useJSXTextNode: false,
            ecmaFeatures: {
                impliedStrict: true,
                globalReturn: false,
                jsx: false,
            },
            babelOptions: {
                configFile: './.babelrc',
            },
        },
        settings: {
            jest: {
                version: 27, // We need this here because of older stylelint plugin
            },
            'import/resolver': {
                'babel-module': {
                    allowExistingDirectories: true,
                    tryExtensions: ['.js', '.ts', '.vue'],
                },
                webpack: {
                    tryConfig: 'webpack.config.js',
                    extensions: ['.js', '.ts', '.vue'],
                },
                node: {
                    tryExtensions: ['.js', '.ts', '.vue'],
                },
            },
            'import/parsers': {
                '@typescript-eslint/parser': ['.ts'],
                'vue-eslint-parser': ['.vue'],
            },
            'import/cache': {
                lifetime: 60,
            },
            ecmascript: 6,
        },
        rules,
        overrides: [
            {
                files: ['*.vue'],
                plugins: ['vue'],
                parser: 'vue-eslint-parser',
                extends: [
                    'plugin:vue/base',
                    'plugin:vue/recommended',
                    '@vue/eslint-config-airbnb',
                    '@vue/eslint-config-prettier',
                ],
                rules: {
                    ...rules,
                    'vue/html-indent': [1, 4],
                },
            },
            {
                files: ['*.test.*'],
                plugins: ['mocha'],
                rules: {
                    ...rules,
                    'mocha/no-exclusive-tests': 'error',
                },
            },
            {
                files: ['*.ts'],
                parser: '@typescript-eslint/parser',
                plugins: ['@typescript-eslint'],
                extends: ['plugin:@typescript-eslint/recommended', 'plugin:import/typescript', 'prettier'],
            },
        ],
    },
    pkg.eslintConfig,
);
