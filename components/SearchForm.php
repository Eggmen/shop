<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;
use Energine\share\gears\DataDescription;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\Field;
use Energine\share\gears\Data;

class SearchForm extends DataSet {

    protected $keyword = '';

    public function __construct($name, array $params = null) {
        parent::__construct($name, $params);
    }

    protected function main() {
        $this->setKeyword(isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '');
        parent::main();
        $this->setType(self::COMPONENT_TYPE_FORM);
    }

    public function setKeyword($keyword) {
        $this -> keyword = $keyword;
        return $this;
    }

    public function getKeyword() {
        return $this->keyword;
    }

    protected function loadData() {
        return [
            ['keyword' => $this->keyword]
        ];
    }

    protected function createDataDescription() {

        $dd = new DataDescription();
        $fd = new FieldDescription('keyword');
        $fd->setType(FieldDescription::FIELD_TYPE_TEXT);
        $dd->addFieldDescription($fd);

        return $dd;
    }

}
