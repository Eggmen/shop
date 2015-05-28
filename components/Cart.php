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
use Energine\share\gears\AttachmentManager;
use Energine\share\gears\ComponentProxyBuilder;
use Energine\share\gears\Data;
use Energine\share\gears\DataDescription;
use Energine\share\gears\EmptyBuilder;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\QAL;
use Energine\share\gears\SimpleBuilder;
use Energine\share\gears\UserSession;
use Energine\shop\gears\CartBuilder;

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

    protected function createBuilder() {
        return new SimpleBuilder();
    }

    protected function createDataDescription() {
        $result = new DataDescription();
        $result->load([
            'cart_id' => [
                'type' => FieldDescription::FIELD_TYPE_INT,
                'index' => 'PRI',
                'tableName' => $this->getTableName(),
            ],
            'cart_date' => [
                'type' => FieldDescription::FIELD_TYPE_DATETIME,
                'tableName' => $this->getTableName(),
            ],
            'cart_goods_count' => [
                'type' => FieldDescription::FIELD_TYPE_INT,
                'tableName' => $this->getTableName(),
            ],
            'goods_id' => [
                'type' => FieldDescription::FIELD_TYPE_INT,
                'tableName' => 'shop_goods',
            ],
            'goods_name' => [
                'type' => FieldDescription::FIELD_TYPE_STRING,
                'tableName' => 'shop_goods_translation',
            ],
            'goods_price' => [
                'type' => FieldDescription::FIELD_TYPE_FLOAT,
                'tableName' => 'shop_goods',
            ],
            'smap_id' => [
                'type' => FieldDescription::FIELD_TYPE_STRING,
                'tableName' => 'shop_goods',
            ],
            'goods_segment' => [
                'type' => FieldDescription::FIELD_TYPE_STRING,
                'tableName' => 'shop_goods',
            ],
            'cart_goods_sum' => [
                'type' => FieldDescription::FIELD_TYPE_FLOAT
            ]
        ]);
        return $result;
    }

    protected function createData() {
        $result = new Data();
        $fields = [];
        foreach ($this->getDataDescription() as $fd) {
            if ($fd->getPropertyValue('tableName'))
                array_push($fields, $fd->getPropertyValue('tableName') . '.' . $fd->getName());
        }
        $request = 'select ' . implode(',', $fields) . ',goods_price*cart_goods_count as cart_goods_sum FROM ' . $this->getTableName() . ' LEFT JOIN shop_goods USING(goods_id)
                        LEFT JOIN shop_goods_translation ON(shop_cart.goods_id=shop_goods_translation.goods_id) AND (lang_id=%s)';
        $data = $this->dbh->select($request, $this->document->getLang());
        if (!empty($data)) {
            $data = array_map(function ($row) {
                $row['smap_id'] = E()->getMap()->getURLByID($row['smap_id']);
                return $row;
            }, $data);
            $result->load($data);
        }

        return $result;
    }


    protected function mainState() {
        $this->setBuilder(new EmptyBuilder());
        $this->setProperty('count', $this->getCount());
        $this->setAction((string)$this->config->getStateConfig('add')->uri_patterns->pattern, true);
        $this->js = $this->buildJS();
    }

    private function getCount() {
        return $this->dbh->getScalar($this->getTableName(), 'SUM(cart_goods_count)', $this->getFilter());
    }

    protected function addState($productID) {

        if ($productID == $this->dbh->getScalar('shop_goods', 'goods_id', ['goods_id' => $productID, 'goods_is_active' => true])) {
            $session = UserSession::start(true);
//            var_dump($session);
            try {
                $this->dbh->modify('INSERT INTO ' . $this->getTableName() . ' (site_id,session_id,u_id, goods_id, cart_goods_count, cart_date) VALUES (%s,%s,%s, %s, 1, %s) ON DUPLICATE KEY UPDATE cart_goods_count=cart_goods_count+1;', (string)E()->getSiteManager()->getCurrentSite(), $session->getID(), (string)($this->document->getUser()->getID()) ?: NULL, $productID, date('Y-m-d H:i:s'));
            } catch (\PDOException $e) {
                inspect($e->getMessage(), (string)$this->document->getUser()->getID());
            }

        }
        $this->showState();
    }

    protected function showState() {
        $this->prepare();
        $am = new AttachmentManager($this->getDataDescription(), $this->getData(), 'shop_goods');
        $am->createFieldDescription();
        $am->createField('goods_id');
        $this->setProperty('count', $this->getCount());

    }

    public function build() {
        if ($this->document->getProperty('single')) {
            E()->getController()->getTransformer()->setFileName('../../../../core/modules/shop/transformers/single_cart.xslt');
        }
        $result = parent::build();
        return $result;
    }
}