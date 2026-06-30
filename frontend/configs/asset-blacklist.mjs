/**
 * Project-level asset compilation blacklist.
 *
 * Component entry points and SCSS styles matching these glob patterns are excluded
 * from the YVES build. Use this to drop core/feature components a project does not use.
 *
 * Patterns support glob syntax: *, **, ?, [], {}
 *
 * Examples:
 *   '**\/LegacyComponent/**'  - exclude any nested LegacyComponent
 *   '**\/DeprecatedWidget/**' - exclude any nested DeprecatedWidget
 */
export default [
    // Add blacklisted component patterns here, e.g.:
    // '**/LegacyComponent/**',
];
