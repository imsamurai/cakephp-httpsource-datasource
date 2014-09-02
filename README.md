HttpSource Plugin [![Main page](http://imsamurai.github.io/cakephp-httpsource-datasource/images/octocat-logo.png)](http://imsamurai.github.io/cakephp-httpsource-datasource/)
=================
[![Build Status](https://travis-ci.org/imsamurai/cakephp-httpsource-datasource.png)](https://travis-ci.org/imsamurai/cakephp-httpsource-datasource) [![Coverage Status](https://coveralls.io/repos/imsamurai/cakephp-httpsource-datasource/badge.png?branch=master)](https://coveralls.io/r/imsamurai/cakephp-httpsource-datasource?branch=master) [![Latest Stable Version](https://poser.pugx.org/imsamurai/http-source/v/stable.png)](https://packagist.org/packages/imsamurai/http-source) [![Total Downloads](https://poser.pugx.org/imsamurai/http-source/downloads.png)](https://packagist.org/packages/imsamurai/http-source) [![Latest Unstable Version](https://poser.pugx.org/imsamurai/http-source/v/unstable.png)](https://packagist.org/packages/imsamurai/http-source) [![License](https://poser.pugx.org/imsamurai/http-source/license.png)](https://packagist.org/packages/imsamurai/http-source)


Plugin with HttpSource to provide base class for datasorses with Http protocol. Based on [ProLoser](https://github.com/ProLoser/CakePHP-Api-Datasources) implementation.
I make some refactoring to make HttpSource more similar to DboSource and removed OAuth component, because i think for login better use [Opauth](https://github.com/uzyn/cakephp-opauth).

For existing plugins check [ProLoser](https://github.com/ProLoser/CakePHP-Api-Datasources) readme. But they will not work with HttpSource. I will adapt these plugins later.

## Notes

`HttpSource` is an **abstract** class and must be extended by the Api you wish to support.
Open a bug ticket if you'd like some help making your own or just want me to do it.
It's _very_ easy to add new ones - [check out the list](#expanding-functionality)

## Installation

### Step 1: Clone or download to `Plugin/HttpSource`

	cd my_cake_app/app
	git clone git://github.com/imsamurai/cakephp-httpsource-datasource.git Plugin/HttpSource

or if you use git add as submodule:

	cd my_cake_app
	git submodule add "git://github.com/imsamurai/cakephp-httpsource-datasource.git" "app/Plugin/HttpSource"

then update submodules:

	git submodule init
	git submodule update

### Step 2: Add your configuration to `database.php` and set it to the model

```
:: database.php ::
```
```php
var $myapi = array(
	'datasource' => 'MyPlugin.Http/MyPlugin', // Example: 'Github.Http/Github'
        'host' => 'api.myplugin.com/v1',
        'port' => 80,
        'persistent' => false,
		'auth' => array(
			'name' => 'oauth',
			'version' => '1.0', //version 2 not tested, maybe don't work
			'oauth_consumer_key' => '--Your API Key--',
			'oauth_consumer_secret' => '--Your API Secret--'
		),
        //all other parameters that passed to config of http socket
        //...
);
```
```
:: MyModel.php ::
```
```php
public $useDbConfig = 'myapi';
public $useTable = 'myapi_table';

```

### Step 3: Load main plugin and your plugin

```
:: bootstrap.php ::
```
```php
CakePlugin::load('HttpSource', array('bootstrap' => true, 'routes' => true));
CakePlugin::load('MyPlugin');
```

### Step 4: Querying the API

Best to just give an example. I switch the datasource on the fly because the model is actually a `projects` table in the
DB. I tend to query from my API and then switch to default and save the results.

```php
App::uses('HttpSourceModel', 'HttpSource.Model');

class Project extends HttpSourceModel {
	function findAuthedUserRepos() {
		$this->setDataSource('github');
		$projects = $this->find('all', array(
                        //used as repo name if useTable is empty, otherwise used as standart fields parameter
			'fields' => 'repos'
		));
		$this->setDataSource('default'); // if more queries are done later
		return $projects;
	}
}
```

## Configuration

See [wiki](https://github.com/imsamurai/cakephp-httpsource-datasource/wiki).
