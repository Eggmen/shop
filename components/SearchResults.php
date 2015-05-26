<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\ComponentProxyBuilder;

class SearchResults extends DataSet {

    /**
     * Bounded component.
     *
     * @var DataSet|boolean $bindComponent
     */
    protected $bindComponent;

    protected $keyword;

    public function __construct($name, array $params = null) {
        parent::__construct($name, $params);

        $this->bindComponent =
            $this->document->componentManager->getBlockByName($this->getParam('bind'));
    }

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'bind' => false,
            ]
        );
    }

    protected function main() {
        parent::main();
        $this->setType(self::COMPONENT_TYPE_LIST);

        $products = ($this->keyword) ? $this->dbh->getColumn(
            'shop_goods_translation',
            'goods_id',
            array(
                'goods_name' => $this->keyword,
                'lang_id' => $this->document->getLang()
            )
        ) : [];

        $this->setBuilder($b = new ComponentProxyBuilder());
        $params = [
            'active' => true,
            'state' => 'main',
            'id'    => $products,
            'list_features' => 'any' // вывод всех фич товаров в списке
        ];
        $b->setComponent('products', '\\Energine\\shop\\components\\GoodsList', $params);
        $toolbars = $this->createToolbar();
        if (!empty($toolbars)) {
            $this->addToolbar($toolbars);
        }
        $this->js = $this->buildJS();
    }

    protected function prepare() {
        if ($this->bindComponent and $this->getState() == 'main') {
            $this->keyword = $this->bindComponent->getKeyword();
            parent::prepare();
        } else {
            $this->disable();
        }
    }
}
