#!/bin/bash

if [ "$PHPDOC" = 1 ]; then
	./travis/phpdoc_after_success.sh;
fi;
if [ "$PHPDOC" != 1 ]; then
	../travis/after_success.sh
fi;
