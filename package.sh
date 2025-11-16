#!/bin/bash

# GCal Availability - Package Script
# Creates a production-ready ZIP file for WordPress plugin installation

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  GCal Availability - Package Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Get plugin version from PHP file
VERSION=$(grep "Version:" gcal-availability.php | awk '{print $3}')
echo -e "${YELLOW}Version:${NC} $VERSION"

# Define output filename
OUTPUT_FILE="gcal-availability.zip"

# Remove old zip if exists
if [ -f "$OUTPUT_FILE" ]; then
    echo -e "${YELLOW}Removing old package:${NC} $OUTPUT_FILE"
    rm "$OUTPUT_FILE"
fi

echo ""
echo -e "${BLUE}Packaging files...${NC}"

# Create zip with only necessary files
zip -r "$OUTPUT_FILE" \
    gcal-availability.php \
    uninstall.php \
    readme.txt \
    README.md \
    assets/ \
    includes/ \
    languages/ \
    -x "*.git*" "*.DS_Store" "node_modules/*" "*.zip" "docs/*"

echo ""
if [ -f "$OUTPUT_FILE" ]; then
    FILE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo -e "${GREEN}✓ Package created successfully!${NC}"
    echo -e "${GREEN}  File: ${NC}$OUTPUT_FILE"
    echo -e "${GREEN}  Size: ${NC}$FILE_SIZE"
    echo ""
    echo -e "${BLUE}Ready to upload to WordPress!${NC}"
    echo -e "  Go to: ${YELLOW}WordPress Admin → Plugins → Add New → Upload Plugin${NC}"
else
    echo -e "${RED}✗ Failed to create package${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}========================================${NC}"

