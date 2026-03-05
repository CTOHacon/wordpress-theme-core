#!/bin/bash
set -e

# Get latest tag, default to v0.0.0 if none exists
LATEST=$(git describe --tags --abbrev=0 2>/dev/null || echo "v0.0.0")
VERSION=${LATEST#v}

IFS='.' read -r MAJOR MINOR PATCH <<< "$VERSION"

NEXT_PATCH="v$MAJOR.$MINOR.$((PATCH + 1))"
NEXT_MINOR="v$MAJOR.$((MINOR + 1)).0"
NEXT_MAJOR="v$((MAJOR + 1)).0.0"

echo "Current version: $LATEST"
echo ""
echo "1) Patch  → $NEXT_PATCH"
echo "2) Minor  → $NEXT_MINOR"
echo "3) Major  → $NEXT_MAJOR"
echo ""
read -p "Select [1/2/3]: " CHOICE

case $CHOICE in
  1) NEXT=$NEXT_PATCH ;;
  2) NEXT=$NEXT_MINOR ;;
  3) NEXT=$NEXT_MAJOR ;;
  *) echo "Invalid choice"; exit 1 ;;
esac

echo ""
read -p "Release $NEXT? (y/n): " CONFIRM
[[ "$CONFIRM" != "y" ]] && echo "Aborted." && exit 0

git tag "$NEXT"
git push origin "$NEXT"

echo ""
echo "Tag $NEXT pushed. GitHub Actions will create the release."
