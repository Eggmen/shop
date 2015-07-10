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
}