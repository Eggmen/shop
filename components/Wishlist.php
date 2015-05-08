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

        //if (!$this->document->getUser()->isAuthenticated()) $this->disable();

        $this->setTableName('shop_wishlist');
        $this->setFilter(['u_id' => $this->document->getUser()->getID()]);
        $this->setOrder(['w_date' => QAL::ASC]);
    }

    protected function main() {
        $this->setBuilder(new EmptyBuilder());
        $this->setProperty('count', $this->getCount());
    }
    private function getCount(){
        return $this->dbh->getScalar($this->getTableName(), 'COUNT(w_id)', $this->getFilter());
    }

    protected function addState($productID) {
        //E()->getController()->getTransformer()->setFileName(CORE_DIR.MODULES.);
        $this->setBuilder(new EmptyBuilder());
        if($this->document->getUser()->isAuthenticated() && $this->dbh->getScalar('shop_goods', 'goods_id', ['goods_id' => $productID])){
            $this->dbh->modify(QAL::INSERT_IGNORE, $this->getTableName(), ['u_id' => $this->document->getUser()->getID(), 'goods_id' => $productID]);

            $this->setProperty('count', $this->getCount());
        }
    }

    protected function deleteState() {

    }

    protected function showState() {

    }
}