<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;
use Energine\share\gears\EmptyBuilder;
use Energine\share\gears\SimpleBuilder;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\Field;
use Energine\share\gears\DataDescription;
use Energine\share\gears\Data;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;

class GoodsCompare extends DataSet
{

    public function __construct($name, array $params = NULL)
    {
        parent::__construct($name, $params);
        // active only in single mode
        $this->setParam('active', ($this -> getProperty('single') != 'single') ? false : true);
        $this->setTitle($this->translate('TXT_COMPARE'));
        $this->setProperty('recordsPerPage', false);
    }

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'goodsTableName' => 'shop_goods',
                'singleTemplate' => '../../../../core/modules/shop/transformers/single_compare.xslt'
            ]
        );
    }

    protected function createBuilder() {
        return new SimpleBuilder();
    }

    protected function main()
    {
        $this->setBuilder(new EmptyBuilder());
        $this->js = $this->buildJS();
    }

    private function getGoodsFromSession() {

        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : array();

        $goods_table = $this -> getParam('goodsTableName');
        $res = $this -> dbh -> select(
            "SELECT g.goods_id, gt.goods_name, gc.smap_id, gct.smap_name
             FROM {$goods_table} g
             LEFT JOIN shop_goods_translation gt on g.goods_id = gt.goods_id and gt.lang_id = %s
             LEFT JOIN share_sitemap gc on g.smap_id = gc.smap_id
             LEFT JOIN share_sitemap_translation gct on gc.smap_id = gct.smap_id and gct.lang_id = %s
             WHERE g.goods_id in (%s)",
            $this -> document -> getLang(),
            $this -> document -> getLang(),
            $goods_ids
        );

        $data = array();
        if ($res) {
            foreach($res as $row) {
                $data['smap_id'][] = $row;
            }
        }

        return $data;
    }

    protected function informer()
    {
        $goods = $this -> getGoodsFromSession();
        $counter = 0;

        $d = new Data();
        $data = array();
        if ($goods) {
            foreach ($goods as $smap_id => $cgoods) {
                $counter = $counter + count($cgoods);
                $current = current($cgoods);
                $ids = array();
                foreach($cgoods as $row) {
                    $ids[] = $row['goods_id'];
                }
                $data[] = array(
                    'smap_id' => $smap_id,
                    'smap_name' => $current['smap_name'],
                    'goods_ids' => implode(',', $ids),
                    'goods_count' => count($cgoods)
                );
            }
        }
        $this->prepare();
        $d -> load($data);
        $this -> setData($d);
        $this->setProperty('goods_count', $counter);
    }

    protected function prepare() {
        // data description для информера
        if (in_array($this -> getState(), ['informer', 'add', 'remove', 'clear'])) {
            $data = new Data();
            $dataDescription = new DataDescription();
            $dataDescription->load(
                array(
                    'smap_id' => array(
                        'key' => true,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_INT,
                        'length' => 10,
                        'index' => 'PRI'
                    ),
                    'smap_name' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'goods_ids' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'goods_count' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    )
                )
            );
            $this->setData($data);
            $this->setDataDescription($dataDescription);
            E()->getController()->getTransformer()->setFileName($this->getParam('singleTemplate'));
            $this->setBuilder($this->createBuilder());
        }
    }

    protected function add()
    {
        $sp = $this -> getStateParams(true);
        $goods_id = $sp['goodsId'];
        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : array();
        if (!in_array($goods_id, $goods_ids)) {
            $_SESSION['goods_compare'][] = $goods_id;
        }

        $this -> informer();
    }

    protected function remove()
    {
        $sp = $this -> getStateParams(true);
        $goods_id = $sp['goodsId'];
        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : array();
        if (in_array($goods_id, $goods_ids)) {
            if (($key = array_search($goods_id, $goods_ids)) !== false) {
                unset($_SESSION['goods_compare'][$key]);
            }
        }

        $this -> informer();
    }

    protected function clear()
    {
        if (!empty($_SESSION['goods_compare'])) {
            $_SESSION['goods_compare'] = [];
        }

        $this -> informer();
    }

    protected function compare()
    {
        parent::prepare();

        $sp = $this -> getStateParams(true);
        $goods_ids = array_filter(explode(',', $sp['goodsIds']), 'is_numeric');

        $params = array(
            'active' => false,
            'state' => 'main',
            'target_ids' => implode(',', $goods_ids), // вывод только заданных id
            'list_features' => 'any' // вывод всех фич товаров в списке
        );

        $this -> setBuilder(new EmptyBuilder());

        $goodsList =
            $this->document->componentManager->createComponent('compareGoodsList', 'Energine\shop\components\GoodsList', $params);
        $this->document->componentManager->add($goodsList);
        $goodsList->run();
    }

}