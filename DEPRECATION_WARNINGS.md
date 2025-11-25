# Deprecation Warnings Explanation

When running `npm install`, you may see deprecation warnings for the following packages. These warnings are **expected and safe to ignore**:

## Fixed Warnings ✅
- `rimraf@2.7.1` and `rimraf@3.0.2` → Now using `rimraf@^5.0.5` via overrides

**Note:** `glob@7.2.3` warning cannot be fixed via overrides because it would break compatibility with `del@4.1.1` (used by `clean-webpack-plugin@3.0.0`). The warning is harmless and does not affect functionality.

## Unavoidable Warnings (Expected) ⚠️

These warnings appear because the packages are:
1. **Deep transitive dependencies** of `@wordpress/scripts`
2. **No newer versions available** or controlled by WordPress tooling
3. **Cannot be overridden** without breaking `@wordpress/scripts` compatibility

### Packages with No Newer Versions:
- `@humanwhocodes/config-array@0.13.0` - Latest version is 0.13.0 (deprecated in favor of `@eslint/config-array`)
- `@humanwhocodes/object-schema@2.0.3` - Latest version is 2.0.3 (deprecated in favor of `@eslint/object-schema`)
- `domexception@4.0.0` - Latest version is 4.0.0 (deprecated in favor of native DOMException)

### Packages Controlled by @wordpress/scripts:
- `eslint@8.57.1` - Required by `@wordpress/scripts`, cannot be upgraded without breaking compatibility
- `abab@2.0.6` - Transitive dependency, deprecated in favor of native browser APIs
- `inflight@1.0.6` - Transitive dependency, deprecated in favor of `lru-cache`
- `intl-messageformat-parser@6.4.4` - Transitive dependency, deprecated in favor of `@formatjs/icu-messageformat-parser`

## Impact

**These warnings do NOT affect:**
- Plugin functionality
- Build process
- Runtime behavior
- Security (they're dev dependencies only)

## Resolution

These warnings will be resolved when:
1. `@wordpress/scripts` updates its dependencies
2. WordPress core updates to newer tooling versions

Until then, these warnings are cosmetic and can be safely ignored.

