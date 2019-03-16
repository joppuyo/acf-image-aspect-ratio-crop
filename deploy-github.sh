#!/usr/bin/env bash
cd ..
git clone https://${GITHUB_API_KEY}@github.com/joppuyo/acf-image-aspect-ratio-crop-packagist-release
rm -fr acf-image-aspect-ratio-crop-packagist-release/*
rm -f acf-image-aspect-ratio-crop-packagist-release/.*
rsync -av --progress acf-image-aspect-ratio-crop/ acf-image-aspect-ratio-crop-packagist-release/ --exclude .git --exclude node_modules --exclude acf-image-aspect-ratio-crop.zip --exclude vendor --exclude=".travis.yml" --exclude=".editorconfig" --exclude=".gitignore" --exclude=".prettierrc.js" --exclude "deploy.sh"
git config --global user.email "johannes@siipo.la"
git config --global user.name "Johannes Siipola"
cd acf-image-aspect-ratio-crop-packagist-release
git add -A -f
git commit -m "Release $TRAVIS_TAG"
git tag "$TRAVIS_TAG"
git push
git push --tags
