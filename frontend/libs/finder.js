const path = require('path');
const glob = require('fast-glob');

// define the default glob settings for fast-glob
const defaultGlobSettings = {
    followSymlinkedDirectories: false,
    absolute: true,
    onlyFiles: true,
    onlyDirectories: false,
};

// perform a search in a list of directories
// matching provided patterns
// using provided glob settings
const globAsync = async (patterns, rootConfiguration) => {
    try {
        return await glob(patterns, rootConfiguration);
    } catch (error) {
        console.error('An error occurred while globbing the system for entry points.', error);
    }
};

/**
 * Resolve globDirs entries that may contain wildcards (*, ?, [], {}, **)
 * into a concrete list of existing directories.
 */
const resolveDirs = async (globDirs) => {
    const hasMagic = (p) => /[*?[\]{}()]/.test(p);
    const sets = await Promise.all(
        globDirs.map(async (d) => {
            if (!hasMagic(d)) {
                // No wildcard: verify with fast-glob (returns [] if not a dir)
                const exact = await glob(d, { onlyDirectories: true, absolute: true, unique: true });
                return exact;
            }
            // Wildcard path: expand to all matching directories
            return await glob(d, { onlyDirectories: true, absolute: true, unique: true });
        }),
    );

    // Flatten + dedupe
    const all = [].concat(...sets);
    return Array.from(new Set(all));
};

const findFiles = (globDirs, globPatterns, globSettings) =>
    (async () => {
        const resolvedDirs = await resolveDirs(globDirs);

        return resolvedDirs.reduce(async (resultsPromise, dir) => {
            const rootConfiguration = {
                ...defaultGlobSettings,
                ...globSettings,
                cwd: dir,
            };

            const results = await resultsPromise;
            const globPath = await globAsync(globPatterns, rootConfiguration);

            return results.concat(globPath);
        }, Promise.resolve([]));
    })();

const find = async (globDirs, globPatterns, globFallbackPatterns, globSettings = {}) => {
    const customThemeFiles = await findFiles(globDirs, globPatterns, globSettings);
    const defaultThemeFiles = globFallbackPatterns.length
        ? await findFiles(globDirs, globFallbackPatterns, globSettings)
        : [];

    return defaultThemeFiles.concat(customThemeFiles);
};

// find entry points
const findEntryPoints = async (settings) => {
    const files = await find(settings.dirs, settings.patterns, settings.fallbackPatterns, settings.globSettings);
    return mergeEntryPoints(files);
};

// merge entry points
const mergeEntryPoints = async (files) =>
    Object.values(
        files.reduce((map, file) => {
            const dir = path.dirname(file);
            const name = path.basename(dir);
            const type = path.basename(path.dirname(dir));
            map[`${type}/${name}`] = file;
            return map;
        }, {}),
    );

// find components entry points
const findComponentEntryPoints = async (settings) => await findEntryPoints(settings);

// find component styles
const findComponentStyles = async (settings) => await find(settings.dirs, settings.patterns, [], settings.globSettings);

// filter blacklisted files (styles or entry points) by glob patterns
const filterBlacklistedFiles = (files, blacklistPatterns = []) => {
    if (!blacklistPatterns || blacklistPatterns.length === 0) {
        return files;
    }

    const { minimatch: matcher } = require('minimatch');

    return files.filter((file) => {
        return !blacklistPatterns.some((pattern) => matcher(file, pattern, { matchBase: true }));
    });
};

const filterBlacklistedStyles = filterBlacklistedFiles;

// find application entry points
const findAppEntryPoint = async (settings, file) => {
    const config = Object.assign({}, settings);
    const updatePatterns = (patternCollection) => patternCollection.map((pattern) => path.join(pattern, file));

    config.patterns = updatePatterns(config.patterns);
    config.fallbackPatterns = updatePatterns(config.fallbackPatterns);

    const entryPoint = await findEntryPoints(config);
    return entryPoint[entryPoint.length - 1];
};

module.exports = {
    findComponentEntryPoints,
    findComponentStyles,
    findAppEntryPoint,
    findFiles,
    filterBlacklistedFiles,
    filterBlacklistedStyles,
};
