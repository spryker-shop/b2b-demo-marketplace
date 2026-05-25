/**
 * Style compilation blacklist
 *
 * Components matching these patterns will not be compiled for SCSS.
 * Patterns support glob syntax: *, **, ?, [], {}
 *
 * Examples:
 * - '*\/LegacyComponent\/**' - exclude any nested LegacyComponent
 */
module.exports = [
    // Add blacklisted component patterns here
    // '**/LegacyComponent/**',
    // '**/DeprecatedComponent/**',
];
