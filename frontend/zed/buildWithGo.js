const { mergeWithCustomize, customizeObject } = require('webpack-merge');
const oryxForZed = require('@spryker/oryx-for-zed');
const path = require('path');

const mergeWithStrategy = mergeWithCustomize({
    customizeObject: customizeObject({
        plugins: 'prepend'
    })
});

const goZedSettings = mergeWithStrategy(oryxForZed.settings, {
    entry: {
        dirs: [path.resolve('./src/Go/Zed/')]
    }
});

oryxForZed.getConfiguration(goZedSettings)
    .then(configuration => oryxForZed.build(configuration, oryxForZed.copyAssets))
    .catch(error => console.error('An error occurred while creating configuration', error));
