<?php
/**
 * Содержит класс Wishlist
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

/**
 * Список пожеланий
 *
 * @package energine
 * @author dr.Pavka
 */
class Wishlist extends DBDataSet {
    public function __construct($name, $module, array $params = NULL) {
        parent::__construct($name, $module, $params);
        $this->setTableName('shop_wishlist');
        $this->setFilter(['site_id' => E()->getSiteManager()->getCurrentSite()->id,'u_id' => $this->document->getUser()->getID()]);
        $this->setOrder(['w_date' => QAL::ASC]);
    }

    protected function mainState() {
        $this->setBuilder(new EmptyBuilder());
        $this->setProperty('count', $this->getCount());
        $this->js = $this->buildJS();
    }

    private function getCount() {
        return $this->dbh->getScalar($this->getTableName(), 'COUNT(w_id)', $this->getFilter());
    }

    protected function addState($productID) {
        E()->getController()->getTransformer()->setFileName('../../../../core/modules/shop/transformers/single_wishlist.xslt');
        $this->setBuilder(new EmptyBuilder());
        if ($this->document->getUser()->isAuthenticated() && $this->dbh->getScalar('shop_goods', 'goods_id', ['goods_id' => $productID])) {
            $this->dbh->modify(QAL::INSERT_IGNORE, $this->getTableName(), ['site_id'=>E()->getSiteManager()->getCurrentSite()->id, 'w_date' => date('Y-m-d H:i:s'), 'u_id' => $this->document->getUser()->getID(), 'goods_id' => $productID]);

            $this->setProperty('count', $this->getCount());
        }
    }

    protected function deleteState() {

    }

    protected function showState() {
        $products = $this->dbh->getColumn($this->getTableName(), 'goods_id', $this->getFilter());
        if (!empty($products)) {
            $this->setBuilder($b = new ComponentProxyBuilder());
            $params = [
                'active' => false,
                'state' => 'main',
                'id'    => $products,
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