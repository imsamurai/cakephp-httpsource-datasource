<?
/**
 * Author: imsamurai <im.samuray@gmail.com>
 * Date: 04.12.2012
 * Time: 17:08:18
 * Format: http://book.cakephp.org/2.0/en/models.html
 */

/**
 * Model with patched methods to wirk with save()
 */
abstract class HttpSource extends AppModel {
    public $name = 'HttpSource';

    public function hasField($name, $checkVirtual = false) {
        return true;
    }

    public function exists($id = null) {
        return true;
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->id = true;
        return parent::save($data, $validate, $fieldList);
    }
}