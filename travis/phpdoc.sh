#!/bin/bash

if [ "$PHPDOC" != 1 ] || [ "$GH_TOKEN" = "" ]; then
	exit 0;
fi;

mkdir -p $HOME/build/docs;
../cakephp/app/vendor/bin/phpdoc -d . -t $HOME/build/docs --template ../cakephp/app/vendor/phpdocumentor/templates/responsive-twig

if [ "$?" -gt 0 ]; then
    exit 1
fi