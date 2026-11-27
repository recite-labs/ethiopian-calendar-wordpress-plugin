#!/usr/bin/env bash
# Build dist/ethiopian-calendar.zip containing only the files WordPress needs.
# The plugin has no runtime Composer dependencies, so no vendor/ is shipped.
set -euo pipefail
cd "$(dirname "$0")/.."

rm -rf dist
mkdir -p dist/ethiopian-calendar
cp -R ethiopian-calendar.php uninstall.php readme.txt LICENSE.txt src assets languages dist/ethiopian-calendar/
rm -rf dist/ethiopian-calendar/assets/screenshot-*.png
(cd dist && zip -qr ethiopian-calendar.zip ethiopian-calendar)
echo "Built dist/ethiopian-calendar.zip"
