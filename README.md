[MyAPI]Source Plugin
=====================

CakePHP [MyAPI]Source Plugin with DataSource for http://www.example.com/

## Installation

### Step 1: Clone or download [HttpSource](https://github.com/imsamurai/cakephp-httpsource-datasource)

### Step 2: Clone or download to `Plugin/[MyAPI]Source`

  cd my_cake_app/app
	[link to repo] Plugin/[MyAPI]Source

or if you use git add as submodule:

	cd my_cake_app
	git submodule add "[link to repo]" "app/Plugin/[MyAPI]Source"

then update submodules:

	git submodule init
	git submodule update

### Step 3: Add your configuration to `database.php` and set it to the model

```
:: database.php ::
public $myapi = array(
  'datasource' => '[MyAPI]Source.Http/[MyAPI]Source',
        'host' => 'www.example.com',
        'port' => 80
);

Then make model

:: [MyAPI]Source.php ::
public $useDbConfig = 'myapi';
public $useTable = '<desired api url ending, for ex: "search">';

```

### Step 4: Load plugin

```
:: bootstrap.php ::
CakePlugin::load('HttpSource', array('bootstrap' => true, 'routes' => false));
CakePlugin::load('[MyAPI]Source');

```

#Documentation

Please read [HttpSource Plugin README](https://github.com/imsamurai/cakephp-httpsource-datasource/blob/master/README.md)