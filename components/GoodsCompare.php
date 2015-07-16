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
use Energine\share\gears\UserSession;

class GoodsCompare extends DataSet implements SampleGoodsCompare{
    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        // active only in single mode
        $this->setParam('active', ($this->getProperty('single') != 'single') ? false : true);
        $this->setTitle($this->translate('TXT_COMPARE'));
        $this->setProperty('recordsPerPage', false);
    }

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'goodsTableName' => 'shop_goods',
                'goodsListClass' => 'Energine\shop\components\GoodsList',
                'singleTemplate' => '../../../../core/modules/shop/transformers/single_compare.xslt'
            ]
        );
    }

    protected function createBuilder() {
        return new SimpleBuilder();
    }

    protected function main() {
        $this->setBuilder(new EmptyBuilder());
        $this->js = $this->buildJS();
    }

    protected function getGoodsFromSession() {

        E()->UserSession->start();

        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : [];

        $goods_table = $this->getParam('goodsTableName');
        $res = $this->dbh->select(
            "SELECT g.goods_id, gt.goods_name, g.smap_id, gct.smap_name
             FROM {$goods_table} g
             LEFT JOIN shop_goods_translation gt on g.goods_id = gt.goods_id and gt.lang_id = %s
             LEFT JOIN share_sitemap gc on g.smap_id = gc.smap_id
             LEFT JOIN share_sitemap_translation gct on gc.smap_id = gct.smap_id and gct.lang_id = %s
             WHERE g.goods_id in (%s)",
            $this->document->getLang(),
            $this->document->getLang(),
            $goods_ids
        );

        $data = [];
        if ($res) {
            foreach ($res as $row) {
                $data[$row['smap_id']][] = $row;
            }
        }

        return $data;
    }

    protected function informer() {
        $goods = $this->getGoodsFromSession();
        $counter = 0;

        $d = new Data();
        $data = [];
        if ($goods) {
            foreach ($goods as $smap_id => $cgoods) {
                $counter = $counter + count($cgoods);
                $current = current($cgoods);
                $ids = [];
                foreach ($cgoods as $row) {
                    $ids[] = $row['goods_id'];
                }
                $data[] = [
                    'smap_id' => $smap_id,
                    'smap_name' => $current['smap_name'],
                    'goods_ids' => implode(',', $ids),
                    'goods_count' => count($cgoods)
                ];
            }
        }
        $this->prepare();
        $d->load($data);
        $this->setData($d);
        $this->setProperty('goods_count', $counter);
    }

    protected function prepare() {
        // data description для информера
        if (in_array($this->getState(), ['informer', 'add', 'remove', 'clear'])) {
            $data = new Data();
            $dataDescription = new DataDescription();
            $dataDescription->load(
                [
                    'smap_id' => [
                        'key' => true,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_INT,
                        'length' => 10,
                        'index' => 'PRI'
                    ],
                    'smap_name' => [
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ],
                    'goods_ids' => [
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ],
                    'goods_count' => [
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ]
                ]
            );
            $this->setData($data);
            $this->setDataDescription($dataDescription);
            E()->getController()->getTransformer()->setFileName($this->getParam('singleTemplate'));
            $this->setBuilder($this->createBuilder());
        }
    }

    protected function add() {
        E()->UserSession->start();
        $sp = $this->getStateParams(true);
        $goods_id = $sp['goodsId'];
        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : [];
        if (!in_array($goods_id, $goods_ids)) {
            $_SESSION['goods_compare'][] = $goods_id;
        }

        $this->informer();
    }

    protected function remove() {
        E()->UserSession->start();
        $sp = $this->getStateParams(true);
        $goods_id = $sp['goodsId'];
        $goods_ids = (!empty($_SESSION['goods_compare'])) ? $_SESSION['goods_compare'] : [];
        if (in_array($goods_id, $goods_ids)) {
            if (($key = array_search($goods_id, $goods_ids)) !== false) {
                unset($_SESSION['goods_compare'][$key]);
            }
        }

        $this->informer();
    }

    protected function clear() {
        E()->UserSession->start();
        if (!empty($_SESSION['goods_compare'])) {
            $_SESSION['goods_compare'] = [];
        }

        $this->informer();
    }

    public function build() {

        $doc = parent::build();

        if ($this->getState() == 'compare') {
            $doc = $this->buildCompare($doc);
        }

        return $doc;
    }

    protected function buildCompare(\DOMDocument $builderDoc) {
        $sp = $this->getStateParams(true);

        $params = [
            'active' => false,
            'state' => 'main',
            'id' => implode(',', array_filter(explode(',', $sp['goodsIds']), 'is_numeric')), // вывод только заданных id
            'list_features' => 'any' // вывод всех фич товаров в списке
        ];

        $goodsList = $this->document->componentManager->createComponent(
            'compareGoodsList',
            $this->getParam('goodsListClass'),
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

        return $builderDoc;
    }

    protected function compare() {
        parent::prepare();
        $this->setBuilder(new EmptyBuilder());
    }

}
interface SampleGoodsCompare{};