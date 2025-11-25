# Plugin Distribution Guide

This guide explains how to prepare the Virtual Photo Booth plugin for distribution and testing on other WordPress sites.

## What to Include in Distribution

When creating a zip file for distribution, include:

### Required Files:
- ✅ `virtual-photo-booth.php` - Main plugin file
- ✅ `readme.txt` - Plugin readme (required for WordPress.org)
- ✅ `includes/` - All PHP class files
- ✅ `build/` - **Compiled JavaScript and CSS** (required - this is what the plugin uses)
- ✅ `assets/` - Additional CSS and JS files
- ✅ `src/` - Source files (optional but recommended for transparency)

### Files to EXCLUDE:
- ❌ `node_modules/` - Development dependencies (very large, not needed)
- ❌ `package-lock.json` - npm lock file (not needed on production)
- ❌ `package.json` - Can be included if you want, but not required
- ❌ `.git/` - Git repository (if present)
- ❌ `.gitignore` - Git ignore file
- ❌ `DEPRECATION_WARNINGS.md` - Development documentation
- ❌ Any `.zip` files

## Creating a Distribution Zip

### Method 1: Manual (Recommended for Testing)

1. **Ensure build files are up to date:**
   ```bash
   cd wp-content/plugins/virtual-photo-booth
   npm run build
   ```

2. **Create a clean directory:**
   ```bash
   # From the plugins directory
   cd ..
   mkdir virtual-photo-booth-dist
   ```

3. **Copy only the necessary files:**
   ```bash
   cp -r virtual-photo-booth/virtual-photo-booth.php virtual-photo-booth-dist/
   cp -r virtual-photo-booth/readme.txt virtual-photo-booth-dist/
   cp -r virtual-photo-booth/includes virtual-photo-booth-dist/
   cp -r virtual-photo-booth/build virtual-photo-booth-dist/
   cp -r virtual-photo-booth/assets virtual-photo-booth-dist/
   cp -r virtual-photo-booth/src virtual-photo-booth-dist/
   cp -r virtual-photo-booth/index.php virtual-photo-booth-dist/
   ```

4. **Create the zip:**
   ```bash
   cd virtual-photo-booth-dist
   zip -r ../virtual-photo-booth.zip .
   ```

### Method 2: Using a Script

Create a `create-distribution.sh` script (see below).

### Method 3: Using .zipignore (if your zip tool supports it)

Some zip tools support `.zipignore` files similar to `.gitignore`.

## Important Notes

1. **Build Files are Required**: The `build/` directory contains the compiled JavaScript and CSS that the plugin uses. Without it, blocks won't work.

2. **Source Files are Optional**: The `src/` directory contains the source files. Including them is good for transparency and allows others to rebuild, but the plugin will work without them if only `build/` is included.

3. **Node Files Not Needed**: The plugin doesn't require Node.js or npm to run on the production site. Only the compiled files in `build/` are needed.

4. **Testing Checklist**:
   - [ ] Run `npm run build` to ensure latest build files
   - [ ] Test plugin activation
   - [ ] Verify blocks appear in editor
   - [ ] Test photo upload functionality
   - [ ] Verify moderation page is accessible
   - [ ] Check that no errors appear in debug.log

## Installation on Another Site

1. Upload the zip file via WordPress admin (Plugins → Add New → Upload Plugin)
2. Activate the plugin
3. The plugin should work immediately - no npm install needed!

## For WordPress.org Submission

If submitting to WordPress.org, you'll need:
- `readme.txt` in WordPress.org format
- All PHP files
- `build/` directory with compiled assets
- No `node_modules/` or development files
- Follow WordPress.org plugin guidelines


