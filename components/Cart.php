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
            'site_id' => E()->getSiteManager()->getCurrentSite()->id,
            'session_id' => UserSession::start()->getID()
        ]);

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
}