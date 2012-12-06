<?

/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 23:20:06
 * Format: http://book.cakephp.org/2.0/en/development/testing.html
 */

class HttpSourceConfigTest extends CakeTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function testConfig() {
        $CF = HttpSourceConfigFactory::instance();

        //standart configs
        $read = array(
            'test/get_dics_info' => array(
                'test/get_dics_info' => array(
                    'required' => array(
                        'id',
                        'pin'
                    ),
                    'optional' => array(
                        'asm_normalize_terms',
                        'param1',
                        'param2'
                    ),
                    'defaults' => array(
                        'asm_normalize_terms' => false
                    ),
                    'map_conditions' => array(
                        'param1' => 'wasparam1',
                        'param2' => array(
                            'condition' => 'wasparam2',
                            'callback' => function ($val) {
                                return 'sadas' . $val;
                            }
                        )
                    ),
                    'map_fields' => array(
                        'terms' => array(
                            'field' => 'terms',
                            'callback' => function ($value) {
                                return array_map('rawurldecode', (array) $value);
                            }
                        )
                    ),
                    'map_results' => function ($result) {
                        return empty($result[0]) ? array() : $result[0];
                    }
                )
            )
        );
        $Config = $CF->config();
        $Config->add(
                $CF->endpoint()
                        ->methodRead()
                        ->table('test/get_dics_info')
                        ->addField(
                                $CF->field()
                                ->name('terms')
                                ->map(function ($value) {
                                            return array_map('rawurldecode', (array) $value);
                                        })
                        )
                        ->addCondition(
                                $CF->condition()
                                ->name('id')
                                ->null(false)
                                ->keyPrimary()
                        )
                        ->addCondition(
                                $CF->condition()
                                ->name('pin')
                                ->null(false)
                        )
                        ->addCondition(
                                $CF->condition()
                                ->name('asm_normalize_terms')
                                ->null(true)
                                ->defaults(false)
                        )
                        ->addCondition(
                                $CF->condition()
                                ->name('param1')
                                ->map(null, 'wasparam1')
                        )
                        ->addCondition(
                                $CF->condition()
                                ->name('param2')
                                ->map(function ($val) {
                                            return 'sadas' . $val;
                                        }, 'wasparam1')
                        )
        );

        debug($Config->endpoint(HttpSourceEndpoint::METHOD_READ, 'test/get_dics_info', array('id','pin'))->schema());


        $this->assertTrue(true);
    }

}