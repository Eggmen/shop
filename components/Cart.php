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

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'active' => true
            ]
        );
    }

    protected function mainState() {
        $this->setBuilder(new EmptyBuilder());
        $this->setProperty('count', $this->getCount());
        $this->js = $this->buildJS();
    }

    private function getCount() {
        return $this->dbh->getScalar($this->getTableName(), 'COUNT(cart_id)', $this->getFilter());
    }

    protected function addState($productID) {

        if($productID == $this->dbh->getScalar('shop_goods', 'goods_id', ['goods_id' => $productID, 'goods_is_active' => true])){
            $session = UserSession::start(true);
//            var_dump($session);
            try {
                $this->dbh->modify('INSERT INTO '.$this->getTableName().' (site_id,session_id,u_id, goods_id, cart_goods_count, cart_date) VALUES (%s,%s,%s, %s, 1, %s) ON DUPLICATE KEY UPDATE cart_goods_count=cart_goods_count+1;', (string)E()->getSiteManager()->getCurrentSite(), $session->getID(), (string)($this->document->getUser()->getID())?:NULL, $productID, date('Y-m-d H:i:s'));
            }
            catch(\PDOException $e){
                inspect($e->getMessage(), (string)$this->document->getUser()->getID());
            }

        }
        $this->showState();
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