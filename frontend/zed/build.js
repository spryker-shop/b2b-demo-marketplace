const { mergeWithCustomize, customizeObject } = require('webpack-merge');
const oryxForZed = require('@spryker/oryx-for-zed');
const path = require('path');

const mergeWithStrategy = mergeWithCustomize({
    customizeObject: customizeObject({
        plugins: 'prepend',
    }),
});

const myCustomZedSettings = mergeWithStrategy(oryxForZed.settings, {
    entry: {
        dirs: [path.resolve('./src/Pyz/Zed/')], // Path for entry points on project level
    },
});

oryxForZed
    .getConfiguration(myCustomZedSettings)
    .then((configuration) => {
        // Force `require('stream')` to resolve to stream-browserify.
        // style-dictionary (used for design tokens) transitively installs a broken `stream@0.0.3`
        // shim via @bundled-es-modules/glob+memfs. Webpack's resolve.fallback doesn't apply when
        // the module exists, so Zed bundles (e.g. chart-plotly → probe-image-size) get the broken
        // shim and crash with `Transform.prototype undefined` in the browser.
        configuration.resolve.alias = {
            ...(configuration.resolve.alias ?? {}),
            stream: require.resolve('stream-browserify'),
        };
        return oryxForZed.build(configuration, oryxForZed.copyAssets);
    })
    .catch((error) => console.error('An error occurred while creating configuration', error));
