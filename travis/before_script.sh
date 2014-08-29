#!/bin/bash

if [ "$PHPDOC" = 1 ]; then
	sudo apt-get -qq update > /dev/null;
	sudo apt-get -qq install graphviz > /dev/null;
fi;

git clone https://github.com/FriendsOfCake/travis.git --depth 1 ../travis;
../travis/before_script.sh;
if [ "$PHPCS" != 1 ]; then
	echo "
		CakePlugin::load('Environment', array('bootstrap' => true));
		CakePlugin::load('AdvancedShell', array('bootstrap' => true));
		CakePlugin::load('HttpSource', array('bootstrap' => true, 'routes' => false));
	" >> ../cakephp/app/Config/bootstrap.php;
	echo "<?php
		require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
		require_once dirname(dirname(dirname(__FILE__))) . '/lib/Cake/Console/ShellDispatcher.php';
		return ShellDispatcher::run(\$argv);
	" > ../cakephp/app/Console/cake.php;
fi;
if [ "$PHPCS" = 1 ]; then
	rm -rf ~/.phpenv/versions/$(phpenv version-name)/pear/PHP/CodeSniffer/Standards/CakePHP;
	git clone https://github.com/imsamurai/cakephp-codesniffer.git --depth 1 ~/.phpenv/versions/$(phpenv version-name)/pear/PHP/CodeSniffer/Standards/CakePHP;
fi;
