#!/usr/bin/env bash

# Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# Delete trunk
rm -Rf ../svn/trunk/*

# Copy plugin content to SNV trunk/ directory
cp -R ./acf-image-aspect-ratio-crop/* ../svn/trunk/

# Switch to SVN repository
cd ../svn/trunk/

# Go to SVN repository root
cd ../

# Add files
svn add --force * --auto-props --parents --depth infinity -q

# Remove files
MISSING_PATHS=$( svn status | sed -e '/^!/!d' -e 's/^!//' )

for MISSING_PATH in $MISSING_PATHS; do
    svn rm --force "$MISSING_PATH"
done

# Push SVN tag
svn ci  --message "Release $TRAVIS_TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive
