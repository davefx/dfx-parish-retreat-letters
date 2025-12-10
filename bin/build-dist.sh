#!/bin/bash
# Distribution Package Build Script for DFX Parish Retreat Letters
# Creates a production-ready ZIP package for WordPress plugin distribution

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory and plugin root
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PLUGIN_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
PLUGIN_SLUG="dfx-parish-retreat-letters"

# Get version from main plugin file
VERSION=$(grep -m 1 "Version:" "$PLUGIN_ROOT/dfx-parish-retreat-letters.php" | awk '{print $3}')

if [ -z "$VERSION" ]; then
    echo -e "${RED}Error: Could not determine plugin version${NC}"
    exit 1
fi

echo -e "${BLUE}==================================================${NC}"
echo -e "${BLUE}DFX Parish Retreat Letters - Distribution Builder${NC}"
echo -e "${BLUE}==================================================${NC}"
echo -e "${GREEN}Plugin:${NC} $PLUGIN_SLUG"
echo -e "${GREEN}Version:${NC} $VERSION"
echo ""

# Define build directory
BUILD_DIR="$PLUGIN_ROOT/build"
DIST_DIR="$BUILD_DIR/$PLUGIN_SLUG"
DIST_FILE="$BUILD_DIR/${PLUGIN_SLUG}-${VERSION}.zip"

# Clean up any previous build
if [ -d "$BUILD_DIR" ]; then
    echo -e "${YELLOW}Cleaning up previous build...${NC}"
    rm -rf "$BUILD_DIR"
fi

# Create build directory structure
echo -e "${BLUE}Creating build directory...${NC}"
mkdir -p "$DIST_DIR"

# Copy files, respecting .distignore
echo -e "${BLUE}Copying plugin files...${NC}"

# Read .distignore patterns into array
declare -a IGNORE_PATTERNS=()
if [ -f "$PLUGIN_ROOT/.distignore" ]; then
    while IFS= read -r line; do
        # Skip empty lines and comments
        [[ -z "$line" || "$line" =~ ^# ]] && continue
        IGNORE_PATTERNS+=("$line")
    done < "$PLUGIN_ROOT/.distignore"
fi

# Build rsync exclude arguments
RSYNC_EXCLUDES=()
for pattern in "${IGNORE_PATTERNS[@]}"; do
    RSYNC_EXCLUDES+=(--exclude="$pattern")
done

# Copy files using rsync with exclusions
rsync -av \
    "${RSYNC_EXCLUDES[@]}" \
    --exclude="build" \
    --exclude=".git" \
    --exclude="*.orig" \
    --exclude="*.rej" \
    --exclude="*~" \
    --exclude="*.bak" \
    --exclude="*.tmp" \
    --exclude="*.swn" \
    --exclude="*.swo" \
    --exclude="*.swp" \
    "$PLUGIN_ROOT/" \
    "$DIST_DIR/"

echo -e "${GREEN}✓ Files copied successfully${NC}"

# Display included files structure
echo ""
echo -e "${BLUE}Distribution package structure:${NC}"
cd "$DIST_DIR" && find . -type f | head -20
FILE_COUNT=$(find . -type f | wc -l)
echo -e "${YELLOW}... and $((FILE_COUNT - 20)) more files${NC}" && cd "$PLUGIN_ROOT"
echo ""

# Create ZIP archive
echo -e "${BLUE}Creating ZIP archive...${NC}"
cd "$BUILD_DIR"
zip -r -q "${PLUGIN_SLUG}-${VERSION}.zip" "$PLUGIN_SLUG"
cd "$PLUGIN_ROOT"

# Get ZIP file size
ZIP_SIZE=$(du -h "$DIST_FILE" | cut -f1)

echo -e "${GREEN}✓ Distribution package created successfully!${NC}"
echo ""
echo -e "${BLUE}==================================================${NC}"
echo -e "${GREEN}Package Details:${NC}"
echo -e "  Location: ${DIST_FILE}"
echo -e "  Size: ${ZIP_SIZE}"
echo -e "  Files: ${FILE_COUNT}"
echo -e "${BLUE}==================================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Test the package by installing in a WordPress site"
echo -e "  2. Upload to WordPress.org or distribute as needed"
echo -e "  3. Clean up: rm -rf $BUILD_DIR"
echo ""

exit 0
