<?php
/**
 * @file
 * OrderGoodsEditor
 *
 * It contains the definition to:
 * @code
 * class OrderGoodsEditor;
 * @endcode
 *
 * @author andy.karpov
 * @copyright Energine 2015
 *
 * @version 1.0.0
 */
namespace Energine\shop\components;
use Energine\share\components\Grid;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\JSONCustomBuilder;


/**
 * Order Goods editor.
 *
 * @code
 * class OrderGoodsEditor;
 * @endcode
 */
class OrderGoodsEditor extends Grid {

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name,  array $params = null) {
        parent::__construct($name, $params);
        $this->setTableName('shop_orders_goods');

        if ($this->getParam('orderID')) {
            $filter = sprintf(' (order_id = %s) ', $this->getParam('orderID'));
        } else {
            $filter = sprintf(' (order_id IS NULL and session_id="%s") ', session_id());
        }

        $this->setFilter($filter);
    }

    /**
     * @copydoc Grid::defineParams
     */
    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            array(
                'orderID' => false,
            )
        );
    }

    public function add() {
        parent::add();
        $data = $this->getData();
        if ($order_id = $this -> getParam('orderID')) {
            $f = $data->getFieldByName('order_id');
            $f->setRowData(0, $order_id);
        }
        $f = $data->getFieldByName('session_id');
        $f->setRowData(0, session_id());
    }

    public function edit() {
        parent::edit();
        if ($order_id = $this -> getParam('orderID')) {
            $data = $this->getData();
            $f = $data->getFieldByName('order_id');
            $f->setRowData(0, $order_id);
        }
    }

    protected function createDataDescription()
    {
        $result = parent::createDataDescription();

        if (in_array($this->getState(), array('add', 'edit'))) {
            $fd = $result->getFieldDescriptionByName('order_id');
            $fd->setType(FieldDescription::FIELD_TYPE_HIDDEN);
            $fd = $result->getFieldDescriptionByName('session_id');
            $fd->setType(FieldDescription::FIELD_TYPE_HIDDEN);
        }

        return $result;
    }

    protected function goodsTotal() {

        $sp = $this->getStateParams(true);
        $goodsID = isset($sp['goods_id']) ? $sp['goods_id'] : 0;

        $this->setBuilder(new JSONCustomBuilder());

        $goods_price = $this->dbh->getScalar('shop_goods', 'goods_price', ['goods_id' => $goodsID]);

        $quanity = (isset($_REQUEST['goods_quantity']) and is_numeric($_REQUEST['goods_quantity'])) ? $_REQUEST['goods_quantity'] : 1;
        $price = (isset($_REQUEST['goods_price']) and is_numeric($_REQUEST['goods_price'])) ? $_REQUEST['goods_price'] : $goods_price;

        $amount = $price * $quanity;

        $b = $this->getBuilder();
        $b->setProperty('result', true)
            ->setProperty('goods_amount', number_format($amount, 2, '.', ''));
    }

    protected function goodsDetails() {

        $sp = $this->getStateParams(true);
        $goodsID = isset($sp['goods_id']) ? $sp['goods_id'] : 0;

        $this->setBuilder(new JSONCustomBuilder());
        $res = $this->dbh->select(
            'select g.goods_price, gt.goods_name, g.goods_code, gt.goods_description_rtf
            from shop_goods g
            left join shop_goods_translation gt on g.goods_id = gt.goods_id and gt.lang_id = %s
            where g.goods_id = %s',
            $this->document->getLang(),
            $goodsID
        );
        $b = $this->getBuilder();
        $b->setProperty('result', (($res) ? true : false));
        if ($res) {
            $b
                ->setProperty('goods_price', number_format($res[0]['goods_price'], 2, '.', ''))
                ->setProperty('goods_title', $res[0]['goods_name'])
                ->setProperty('goods_description', strip_tags($res[0]['goods_description_rtf']))
                ->setProperty('goods_code', $res[0]['goods_code'])
                ->setProperty('goods_quantity', 1)
                ->setProperty('goods_amount', number_format($res[0]['goods_price'], 2, '.', ''))
            ;
        }
    }
}