export default {
    source: ['tokens.sd.json'],
    platforms: {
        css: {
            transformGroup: 'css',
            buildPath: 'dist/',
            files: [
                {
                    destination: 'variables.css',
                    format: 'css/variables',
                    options: {
                        selector: ':root',
                        outputReferences: true
                    }
                }
            ]
        }
    }
};
