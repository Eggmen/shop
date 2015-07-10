<?php

/**
 * @file OrderEditor
 *
 * It contains the definition to:
 * @code class OrderEditor; @endcode
 *
 * @author andy.karpov
 * @copyright Energine 2015
 *
 * @version 1.0.0
 */

namespace Energine\shop\components;

use Energine\share\components\Grid,
    Energine\share\gears\FieldDescription,
    Energine\share\gears\Field,
    Energine\share\gears\ComponentManager,
    Energine\share\gears\Sitemap;
use Energine\share\gears\Data;
use Energine\share\gears\DataDescription;
use Energine\share\gears\JSONCustomBuilder;

/**
 * Order editor.
 *
 * @code
 * class OrderEditor;
 * @endcode
 */
class OrderEditor extends Grid implements SampleOrderEditor {

    /**
     * Order Goods editor.
     * @var OrderGoodsEditor $orderGoodsEditor
     */
    protected $orderGoodsEditor;

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        $this->setTableName('shop_orders');

        if ($sites = E()->getSiteManager()->getSitesByTag('shop')) {
            $site_ids = array_map(function ($site) {
                return (string)$site;
            }, $sites);

            $this->setFilter([
                'site_id' => $site_ids
            ]);
        }
    }

    /**
     * Added "goods" data description to the forms
     *
     * @throws \Energine\share\gears\SystemException
     */
    protected function prepare() {

        parent::prepare();

        if (in_array($this->getState(), ['add', 'edit'])) {

            // relations
            $fd = new FieldDescription('goods');
            $fd->setType(FieldDescription::FIELD_TYPE_TAB);
            $fd->setProperty('title', $this->translate('TAB_ORDER_GOODS'));
            $this->getDataDescription()->addFieldDescription($fd);

            $field = new Field('goods');
            $state = $this->getState();
            $tab_url = (($state != 'add') ? $this->getData()->getFieldByName($this->getPK())->getRowData(0) : '') . '/goods/';

            $field->setData($tab_url, true);
            $this->getData()->addField($field);
        }
    }

    /**
     * Create component for editing ordered goods.
     */
    protected function orderGoodsEditor() {
        $sp = $this->getStateParams(true);
        $params = ['config' => 'core/modules/shop/config/OrderGoodsEditor.component.xml'];

        if (isset($sp['order_id'])) {
            $this->request->shiftPath(2);
            $params['orderID'] = $sp['order_id'];

        } else {
            $this->request->shiftPath(1);
        }
        $this->orderGoodsEditor = $this->document->componentManager->createComponent('orderGoodsEditor', 'Energine\shop\components\OrderGoodsEditor', $params);
        $this->orderGoodsEditor->run();
    }

    /**
     * @copydoc GoodsEditor::build
     */
    public function build() {
        if ($this->getState() == 'orderGoodsEditor') {
            $result = $this->orderGoodsEditor->build();
        } else {
            $result = parent::build();
        }
        return $result;
    }

    /**
     * @copydoc Grid::saveData
     */
    protected function saveData() {
        $orderID = parent::saveData();
        $this->saveOrderGoods($orderID);
        return $orderID;
    }

    /**
     * Link order goods to the current order_id (after save)
     *
     * @param int $orderID
     * @throws \Energine\share\gears\SystemException
     */
    protected function saveOrderGoods($orderID) {
        $this->dbh->modify(
            'UPDATE shop_orders_goods
			SET session_id = NULL, order_id=%s
			WHERE (order_id IS NULL AND session_id=%s) or (order_id = %1$s)',
            $orderID, session_id()
        );
    }

    protected function orderTotal() {

        $sp = $this->getStateParams(true);
        $orderID = isset($sp['order_id']) ? $sp['order_id'] : 0;

        $this->setBuilder(new JSONCustomBuilder());
        $amount = $this->dbh->getScalar(
            'select SUM(goods_price*goods_quantity) from shop_orders_goods
             where order_id = %s or (order_id is NULL and session_id = %s)',
            $orderID, session_id()
        );
        $discount = (isset($_REQUEST['order_discount']) and is_numeric($_REQUEST['order_discount'])) ? $_REQUEST['order_discount'] : 0;
        $total = $amount - $discount;
        $b = $this->getBuilder();
        $b->setProperty('result', true)
            ->setProperty('amount', number_format($amount, 2, '.', ''))
            ->setProperty('total', number_format($total, 2, '.', ''));
    }

    protected function userDetails() {

        $sp = $this->getStateParams(true);
        $uID = isset($sp['u_id']) ? $sp['u_id'] : 0;

        $this->setBuilder(new JSONCustomBuilder());
        $res = $this->dbh->select(
            'select u_name as email, u_fullname as user_name, u_phone as phone, u_city as city, u_address as address
            from user_users
             where u_id = %s',
            $uID
        );
        $b = $this->getBuilder();
        $b->setProperty('result', (($res) ? true : false));
        if ($res) {
            $b
                ->setProperty('email', $res[0]['email'])
                ->setProperty('user_name', $res[0]['user_name'])
                ->setProperty('phone', $res[0]['phone'])
                ->setProperty('city', $res[0]['city'])
                ->setProperty('address', $res[0]['address'])
            ;
        }
    }


}

interface SampleOrderEditor {

}