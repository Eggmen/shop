<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;
use Energine\share\gears\ComponentProxyBuilder;
use Energine\share\gears\EmptyBuilder;
use Energine\share\gears\QAL;
use Energine\share\gears\UserSession;

class GoodsLastSeenList extends DataSet {

    public function __construct($name, $module, array $params = NULL) {
        parent::__construct($name, $module, $params);
    }

    protected function mainState() {
        E()->UserSession->start();
        if (!empty($_SESSION['last_seen_goods'])) {
            $this->setBuilder($b = new ComponentProxyBuilder());
            $params = [
                'active' => false,
                'state' => 'main',
                'id' => $_SESSION['last_seen_goods'],
                'list_features' => 'any' // вывод всех фич товаров в списке
            ];
            $b->setComponent('products',
                '\\Energine\\shop\\components\\GoodsList',
                $params);
            $toolbars = $this->createToolbar();
            if (!empty($toolbars)) {
                $this->addToolbar($toolbars);
            }
            $this->js = $this->buildJS();
        } else {
            $this->setBuilder(new EmptyBuilder());
        }
    }

}