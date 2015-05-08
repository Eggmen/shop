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
use Energine\share\gears\SimpleBuilder;

/**
 * Список пожеланий
 *
 * @package energine
 * @author dr.Pavka
 */
class Wishlist extends DBDataSet {
    private $products = [];

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

    private function getCount() {
        return $this->dbh->getScalar($this->getTableName(), 'COUNT(w_id)', $this->getFilter());
    }

    protected function addState($productID) {
        E()->getController()->getTransformer()->setFileName('../../../../core/modules/shop/transformers/single_wishlist.xslt');
        $this->setBuilder(new EmptyBuilder());
        if ($this->document->getUser()->isAuthenticated() && $this->dbh->getScalar('shop_goods', 'goods_id', ['goods_id' => $productID])) {
            $this->dbh->modify(QAL::INSERT_IGNORE, $this->getTableName(), ['u_id' => $this->document->getUser()->getID(), 'goods_id' => $productID]);

            $this->setProperty('count', $this->getCount());
        }
    }

    protected function deleteState() {

    }

    protected function showState() {
        $this->setBuilder(new EmptyBuilder());
        $this->products = $this->dbh->getColumn($this->getTableName(), 'goods_id', $this->getFilter());
    }

    public function build() {
        $doc = parent::build();

        if ($this->getState() == 'show') {
            $doc = $this->buildList($doc);
        }

        return $doc;
    }

    private function buildList(\DOMDocument $builderDoc) {
        if (!empty($this->products)) {
            $params = [
                'active' => false,
                'state' => 'main',
                'id' => $this->products,
                'list_features' => 'any' // вывод всех фич товаров в списке
            ];

            $goodsList = $this->document->componentManager->createComponent(
                'products',
                '\\Energine\\shop\\components\\GoodsList',
                $params
            );
            $goodsList->run();
            $goodsDoc = $goodsList->build();

            $builderXpath = new \DOMXPath($builderDoc);
            $recordsets = $builderXpath->query("/component/recordset");

            $goodsXpath = new \DOMXPath($goodsDoc);
            $records = $goodsXpath->query("/component/recordset/record");

            foreach ($records as $record) {
                $record = $builderDoc->importNode($record, true);

                foreach ($recordsets as $recordset) {
                    $recordset->appendChild($record);
                }
            }


        }
        return $builderDoc;
    }
}