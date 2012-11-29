<?php
/**
 * A MyApiSource API Method Map
 *
 * Refer to the HttpSource plugin for how to build a method map
 *
 * @link https://github.com/imsamurai/cakephp-httpsource-datasource
 */
$config['MyApiSource']['oauth'] = array(
	'version' => '1.0', // [Optional] OAuth version (defaults to 1.0): '1.0' or '2.0'
	'scheme' => 'https', // [Optional] Values: 'http' or 'https'
	'authorize' => 'authorize', // Example URI: api.linkedin.com/uas/oauth/authorize
	'request' => 'requestToken',
	'access' => 'accessToken',
	'login' => 'authenticate', // Like authorize, just auto-redirects
	'logout' => 'invalidateToken',
);
$config['MyApiSource']['read'] = array(
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
                                                //...
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
$config['MyApiSource']['create'] = array(
);
$config['MyApiSource']['update'] = array(
);
$config['MyApiSource']['delete'] = array(
);