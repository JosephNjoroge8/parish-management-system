#!/bin/bash

##############################################################################
# VITE ASSET VERIFICATION SCRIPT
##############################################################################
# This script verifies that Vite assets are properly built and accessible
# Usage: bash verify-vite-assets.sh
##############################################################################

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BUILD_DIR="public/build"
MANIFEST_FILE="$BUILD_DIR/manifest.json"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Vite Asset Verification${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Check if build directory exists
if [ ! -d "$BUILD_DIR" ]; then
    echo -e "${RED}✗ Build directory not found: $BUILD_DIR${NC}"
    echo -e "${YELLOW}  Run: npm run build${NC}\n"
    exit 1
else
    echo -e "${GREEN}✓ Build directory exists${NC}"
fi

# Check if manifest.json exists
if [ ! -f "$MANIFEST_FILE" ]; then
    echo -e "${RED}✗ Manifest file not found: $MANIFEST_FILE${NC}"
    echo -e "${YELLOW}  Run: npm run build${NC}\n"
    exit 1
else
    echo -e "${GREEN}✓ Manifest file exists${NC}"
fi

# Check manifest.json is valid JSON
if ! jq empty "$MANIFEST_FILE" 2>/dev/null; then
    echo -e "${RED}✗ Manifest file is not valid JSON${NC}\n"
    exit 1
else
    echo -e "${GREEN}✓ Manifest file is valid JSON${NC}"
fi

# Count assets in manifest
ASSET_COUNT=$(jq 'length' "$MANIFEST_FILE")
echo -e "${GREEN}✓ Found $ASSET_COUNT assets in manifest${NC}\n"

# Verify critical entry points exist
echo -e "${BLUE}Checking critical assets...${NC}"

# Extract main app entry from manifest
MAIN_JS=$(jq -r '.["resources/js/app.tsx"].file // empty' "$MANIFEST_FILE")
MAIN_CSS=$(jq -r '.["resources/css/app.css"].file // empty' "$MANIFEST_FILE")

ERRORS=0

if [ -z "$MAIN_JS" ]; then
    echo -e "${RED}✗ Main JS entry not found in manifest${NC}"
    ERRORS=$((ERRORS + 1))
else
    if [ -f "$BUILD_DIR/$MAIN_JS" ]; then
        FILE_SIZE=$(stat -f%z "$BUILD_DIR/$MAIN_JS" 2>/dev/null || stat -c%s "$BUILD_DIR/$MAIN_JS" 2>/dev/null)
        echo -e "${GREEN}✓ Main JS exists: $MAIN_JS (${FILE_SIZE} bytes)${NC}"
    else
        echo -e "${RED}✗ Main JS file missing: $BUILD_DIR/$MAIN_JS${NC}"
        ERRORS=$((ERRORS + 1))
    fi
fi

if [ -z "$MAIN_CSS" ]; then
    echo -e "${YELLOW}⚠ Main CSS entry not found in manifest${NC}"
else
    if [ -f "$BUILD_DIR/$MAIN_CSS" ]; then
        FILE_SIZE=$(stat -f%z "$BUILD_DIR/$MAIN_CSS" 2>/dev/null || stat -c%s "$BUILD_DIR/$MAIN_CSS" 2>/dev/null)
        echo -e "${GREEN}✓ Main CSS exists: $MAIN_CSS (${FILE_SIZE} bytes)${NC}"
    else
        echo -e "${RED}✗ Main CSS file missing: $BUILD_DIR/$MAIN_CSS${NC}"
        ERRORS=$((ERRORS + 1))
    fi
fi

# Check for assets directory
if [ -d "$BUILD_DIR/assets" ]; then
    ASSET_FILES=$(find "$BUILD_DIR/assets" -type f | wc -l)
    echo -e "${GREEN}✓ Assets directory exists with $ASSET_FILES files${NC}"
else
    echo -e "${RED}✗ Assets directory missing${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Check file permissions
echo -e "\n${BLUE}Checking permissions...${NC}"
if [ -r "$MANIFEST_FILE" ]; then
    echo -e "${GREEN}✓ Manifest file is readable${NC}"
else
    echo -e "${RED}✗ Manifest file is not readable${NC}"
    ERRORS=$((ERRORS + 1))
fi

# List recent build files
echo -e "\n${BLUE}Recent build files:${NC}"
find "$BUILD_DIR" -type f -mtime -1 2>/dev/null | head -n 10 | while read file; do
    echo -e "  ${file}"
done

# Summary
echo -e "\n${BLUE}========================================${NC}"
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo -e "${GREEN}Assets are ready for production.${NC}"
    exit 0
else
    echo -e "${RED}✗ $ERRORS error(s) found${NC}"
    echo -e "${YELLOW}Please run: npm run build${NC}"
    exit 1
fi
