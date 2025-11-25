#!/bin/bash

# Script to create a clean distribution zip of the Virtual Photo Booth plugin
# Usage: ./create-distribution.sh

set -e

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_NAME="virtual-photo-booth"
DIST_DIR="${PLUGIN_DIR}-dist"
ZIP_FILE="${PLUGIN_DIR}/../${PLUGIN_NAME}.zip"

echo "Creating distribution package for ${PLUGIN_NAME}..."

# Step 1: Ensure build files are up to date
echo "Step 1: Building assets..."
cd "$PLUGIN_DIR"
if [ -f "package.json" ]; then
    npm run build
    echo "✓ Build complete"
else
    echo "⚠ No package.json found - skipping build step"
fi

# Step 2: Create clean distribution directory
echo "Step 2: Creating clean distribution directory..."
if [ -d "$DIST_DIR" ]; then
    rm -rf "$DIST_DIR"
fi
mkdir -p "$DIST_DIR"

# Step 3: Copy required files
echo "Step 3: Copying files..."
cp -r "$PLUGIN_DIR/virtual-photo-booth.php" "$DIST_DIR/"
cp -r "$PLUGIN_DIR/readme.txt" "$DIST_DIR/"
cp -r "$PLUGIN_DIR/index.php" "$DIST_DIR/"
cp -r "$PLUGIN_DIR/includes" "$DIST_DIR/"
cp -r "$PLUGIN_DIR/build" "$DIST_DIR/"
cp -r "$PLUGIN_DIR/assets" "$DIST_DIR/"

# Optionally include source files (uncomment if desired)
# cp -r "$PLUGIN_DIR/src" "$DIST_DIR/"

# Step 4: Create zip file
echo "Step 4: Creating zip file..."
cd "$DIST_DIR"
if [ -f "$ZIP_FILE" ]; then
    rm "$ZIP_FILE"
fi
zip -r "$ZIP_FILE" . -q

# Step 5: Clean up
echo "Step 5: Cleaning up..."
cd "$PLUGIN_DIR"
rm -rf "$DIST_DIR"

# Done
echo ""
echo "✓ Distribution package created successfully!"
echo "  Location: $ZIP_FILE"
echo "  Size: $(du -h "$ZIP_FILE" | cut -f1)"
echo ""
echo "Files included:"
unzip -l "$ZIP_FILE" | tail -n +4 | head -n -2 | awk '{print "  " $4}'
echo ""
echo "You can now upload this zip file to any WordPress site!"


