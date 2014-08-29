#!/bin/bash

if [ "$PHPDOC" != 1 ] || [ "$GH_TOKEN" = "" ]; then
	exit 0;
fi;

echo -e "Publishing PHPDoc...\n"
echo $HOME;
echo $TRAVIS_REPO_SLUG;

## Copie de la documentation generee dans le $HOME
cp -R $HOME/build/docs $HOME/docs-latest

cd $HOME
## Initialisation et recuperation de la branche gh-pages du depot Git
git config --global user.email "travis@travis-ci.org"
git config --global user.name "travis-ci"
git clone --quiet --branch=gh-pages https://${GH_TOKEN}@github.com/${TRAVIS_REPO_SLUG} gh-pages > /dev/null

cd gh-pages

## Suppression de l'ancienne version
git rm -rf ./docs/$TRAVIS_BRANCH

## Création des dossiers
mkdir docs
cd docs
mkdir $TRAVIS_BRANCH

## Copie de la nouvelle version
cp -Rf $HOME/docs-latest/* ./$TRAVIS_BRANCH/

## On ajoute tout
git add -f .
## On commit
git commit -m "PHPDocumentor (Travis Build : $TRAVIS_BUILD_NUMBER  - Branch : $TRAVIS_BRANCH)"
## On push
git push -fq origin gh-pages > /dev/null
if [ "$?" -gt 0 ]; then
    echo -e "Can't publish PHPDoc to gh-pages.\n";
    exit 1;
fi
## Et c est en ligne !
echo -e "Published PHPDoc to gh-pages.\n"
