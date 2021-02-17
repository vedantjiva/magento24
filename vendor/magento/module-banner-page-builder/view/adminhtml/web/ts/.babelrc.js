/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

const tsDirectory = 'app/code/Magento/BannerPageBuilder/view/adminhtml/web/ts/';
const moduleName = 'Magento_BannerPageBuilder';

module.exports = {
    passPerPreset: true,
    presets: [
        {
            plugins: [
                ['@babel/plugin-proposal-class-properties', {
                    loose: true
                }],
                '@babel/plugin-transform-modules-amd',
                '../../../../../PageBuilder/view/adminhtml/web/ts/babel/plugin-amd-to-magento-amd',
            ]
        },
        [
            '@babel/preset-env',
            {
                loose: true,
                targets: {
                    browsers: ['last 2 versions', 'ie >= 11']
                },
                modules: 'amd'
            }
        ]
    ],
    plugins: [
        '@babel/plugin-transform-typescript',
        ['../../../../../PageBuilder/view/adminhtml/web/ts/babel/plugin-resolve-magento-imports', {
            path: tsDirectory,
            prefix: moduleName
        }],
        ['@comandeer/babel-plugin-banner', {
            'banner': "/*eslint-disable */\n"
        }],
        '@babel/plugin-syntax-object-rest-spread'
    ],
    ignore: [
        '/**/*.d.ts',
        '/**/*.types.ts',
    ]
};
