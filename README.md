# HttpSource Plugin

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
var $myapi = array(
	'datasource' => 'MyPlugin.Http/MyPlugin', // Example: 'Github.Http/Github'
        'host' => 'api.myplugin.com/v1',
        'port' => 80,
        'persistent' => false,
	// These are only required for authenticated requests (write-access)
	'login' => '--Your API Key--',
	'password' => '--Your API Secret--',
        //all other parameters that passed to config of http socket
        //...
);

:: MyModel.php ::
public $useDbConfig = 'myapi';
public $useTable = 'myapi_table';

```

### Step 3: Load main plugin and your plugin

```
:: bootstrap.php ::
CakePlugin::load('HttpSource', array('bootstrap' => true, 'routes' => false));
CakePlugin::load('MyPlugin');

```

### Step 4: Querying the API

Best to just give an example. I switch the datasource on the fly because the model is actually a `projects` table in the
DB. I tend to query from my API and then switch to default and save the results.

```
Class Project extends AppModel {
	function findAuthedUserRepos() {
		$this->setDataSource('github');
		$projects = $this->find('all', array(
			'fields' => 'repos'
		));
		$this->setDataSource('default'); // if more queries are done later
		return $projects;
	}
}
```

## Expanding functionality

### Creating a configuration map

_[MyPlugin]/Config/[MyPlugin].php_

REST paths must be ordered from most specific conditions to least (or none). This is because the map is iterated through
until the first path which has all of its required conditions met is found. If a path has no required conditions, it will
be used. Optional conditions aren't checked, but are added when building the request.

```
$config['MyPlugin']['oauth'] = array(
	'version' => '1.0', // [Optional] OAuth version (defaults to 1.0): '1.0' or '2.0'
	'scheme' => 'https', // [Optional] Values: 'http' or 'https'
	'authorize' => 'authorize', // Example URI: api.linkedin.com/uas/oauth/authorize
	'request' => 'requestToken',
	'access' => 'accessToken',
	'login' => 'authenticate', // Like authorize, just auto-redirects
	'logout' => 'invalidateToken',
);
$config['MyPlugin']['read'] = array(
	// field
	'people' => array(
		// api url
		'people/id=' => array(
			// required conditions
			'required' => array('id'),

                        //additionally you can map fields with or without callbacks
                        //field names are Hash path compatible
                        'map_fields' => array(
                            'new_path.name' => 'old_path.name',
                            'new_path_name' => 'old_path_name',
                            'new_array_path_name' => array(
                                'field' => 'old.field_string',
                                //any callable construction that expect one param with value
                                'callback' => function($value, Model $model) {
                                    return explode(',', $value);
                                }
                            ),
                        ),

                        //additionally you can map conditions with or without callbacks
                        //field names are *NOT* Hash path compatible
                        'map_conditions' => array (
                            'new_param_name' => 'old_param_name',
                            'new_param_name2' => array(
                                'condition' => 'old_param_name2',
                                'callback' => function($value) {
                                                ...
                                                return $new_value;
                                                }
                            )
                        )
		),
		'people/url=' => array(
			'required' => array('url'),
                        /**
                         * default values for required and optional conditions
                         */
                        'defaults' => array(
                                        'url' => 'http://example.com/'
                                      )
		),
		'people/~' => array(),
	),
	'people-search' => array(
		'people-search' => array(
		// optional conditions the api call can take
			'optional' => array(
				'keywords'
			),
		),
	),
);
$config['MyPlugin']['create'] = array(
);
$config['MyPlugin']['update'] = array(
);
$config['MyPlugin']['delete'] = array(
);
/**
 * Map parameters like limit, offset, etc to conditions
 * by config rules
 *
 * For example to map limit to parameter count:
 *    array(
 *      'count' => 'limit+offset'
 *    );
 */
$config['MyPlugin']['map_read_params'] = array(
);
//Cache configuration name or false or null or '' (no cache)
$config['MyPlugin']['cache'] = false;
```

### Note:
Format `$config['Apis']['MyPlugin']` not supported.


### Creating a custom datasource

Try browsing the apis datasource and seeing what automagic functionality you can hook into.

_[MyPlugin]/Model/Datasource/Http/[MyPlugin].php_

```
App::uses('HttpSource', 'HttpSource.Model/Datasource');
Class MyPlugin extends HttpSource {
	// Examples of overriding methods & attributes:
	public $options = array(
		'format'    => 'json',
		'ps'		=> '&', // param separator
		'kvs'		=> '=', // key-value separator
	);
	// Key => Values substitutions in the uri-path right before the request is made. Scans uri-path for :keyname
	public $tokens = array();
	// Enable OAuth for the api
	public function __construct($config) {
		$config['method'] = 'OAuth'; // or 'OAuthV2'
		parent::__construct($config);
	}
	// Last minute tweaks
	public function beforeRequest($request) {
		$request['header']['x-li-format'] = $this->options['format'];
		return $request;
	}
}
```

### On-the-fly customization
Lets say you don't feel like bothering to make a new plugin just to support your api, or the existing plugin doesn't cover
enough of the features. Good news! The plugin degrades gracefully and allows you to manually manipulate the request (thanks
to NeilCrookes' RESTful plugin).

You can use `query` method (like with DBO) with one argument request array or uri string.

Simply populate Model->request with any request params you wish and then fire off the related action. You can even continue
using the `$data` & `$this->data` for `save()` and `update()` or pass a `'path'` key to `find()` and it will automagically
be injected into your request object.

## Roadmap / Concerns

**I'm eager to hear any recommendations or possible solutions.**

* **More automagic**
* **Better map scanning:**
  I'm not sure of a good way to add map scanning to `save()`, `update()` and `delete()` methods yet since I have little control
  over the arguments passed to the datasource. It is easy to supplement `find()` with information and utilize it for processing.
* **Complex query-building versatility:**
  Some APIs have multiple different ways of passing query params. Sometimes within the same request! I still need to flesh
  out param-building functions and options in the driver so that people extending the datasource have less work.