<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;

class GoodsSort extends DataSet {

    protected $sort_data = [];

    public function __construct($name, array $params = NULL) {
        $params['active'] = false;
        parent::__construct($name, $params);


        $this->setTitle($this->translate('TXT_SORT'));
    }

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'bind' => false,
            ]
        );
    }

    protected function loadData() {
        return [$this->sort_data];
    }

    public function main() {
        $goodsList = E()->getDocument()->componentManager->getBlockByName($this->getParam('bind'));
        if($goodsList->getState() == 'view') $this->disable();

        $this->sort_data = $goodsList->getSortData();
        $this->prepare();
        $this->setType(self::COMPONENT_TYPE_FORM);
    }
}