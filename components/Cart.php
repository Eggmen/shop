<?php
/**
 * Содержит класс Basket
 *
 * @package energine
 * @author dr.Pavka
 * @copyright Energine 2015
 */
namespace Energine\shop\components;

use Energine\share\components\DBDataSet;
use Energine\share\gears\ComponentProxyBuilder;
use Energine\share\gears\EmptyBuilder;
use Energine\share\gears\QAL;
use Energine\share\gears\UserSession;

/**
 * Shop basket
 *
 * @package energine
 * @author dr.Pavka
 */
class Cart extends DBDataSet {
    public function __construct($name, $module, array $params = NULL) {
        parent::__construct($name, $module, $params);
        $this->setTableName('shop_cart');
        $this->setFilter([
            'site_id' => E()->getSiteManager()->getCurrentSite()->id
        ]);
        if ($this->document->getUser()->isAuthenticated()) {
            $this->addFilterCondition(['u_id' => $this->document->getUser()->getID()]);
        } else {
            $this->addFilterCondition(['session_id' => UserSession::start()->getID()]);
        }

        $this->setOrder(['cart_date' => QAL::ASC]);
    }

    protected function mainState() {
        $this->setBuilder(new EmptyBuilder());
        $this->setProperty('count', $this->getCount());
        $this->js = $this->buildJS();
    }

    private function getCount() {
        return $this->dbh->getScalar($this->getTableName(), 'COUNT(cart_id)', $this->getFilter());
    }

    protected function showState() {
        $products = $this->dbh->getColumn($this->getTableName(), 'goods_id', $this->getFilter());
        if (!empty($products)) {
            $this->setBuilder($b = new ComponentProxyBuilder());
            $params = [
                'active' => false,
                'state' => 'main',
                'id' => $products,
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