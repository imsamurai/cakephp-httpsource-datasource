#!/bin/bash

if [ "$PHPDOC" = 1 ]; then
	./travis/phpdoc.sh;
fi;
if [ "$PHPDOC" != 1 ]; then
	../travis/script.sh
fi;
