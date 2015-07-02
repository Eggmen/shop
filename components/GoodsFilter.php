<?php

namespace Energine\shop\components;

use Energine\share\components\DataSet;
use Energine\share\gears\Field;
use Energine\share\gears\FieldDescription;
use Energine\shop\gears\EmptySimpleFormBuilder;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;


class GoodsFilter extends DataSet {
    const FILTER_GET = 'filter';
    protected $filter_data = [];
    /**
     * @var GoodsList
     */
    private $boundComponent;

    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        $this->setParam('active', false);
        $this->setTitle($this->translate('TXT_FILTER'));
    }

    protected function createBuilder() {
        return new EmptySimpleFormBuilder();
    }

    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'bind' => false,
                'tableName' => 'shop_goods',
                'active' => false,
                'showForProduct' => false,
                'removeEmptyPriceFilter' => true,
            ]
        );
    }

    protected function buildPriceFilter() {
        if ($fd = $this->getDataDescription()->getFieldDescriptionByName('price')) {
            $fd->setType(FieldDescription::FIELD_TYPE_CUSTOM);
            $fd->setProperty('title', $this->translate('FILTER_PRICE'));
            $fd->setProperty('subtype', FeatureFieldAbstract::FEATURE_FILTER_TYPE_RANGE);
            $min = ceil($this->dbh->getScalar(
                'select min(goods_price) from ' .
                $this->getParam('tableName') .
                ' where smap_id IN( %s)', $this->boundComponent->getCategories()
            ));
            $max = ceil($this->dbh->getScalar(
                'select max(goods_price) from ' .
                $this->getParam('tableName') .
                ' where smap_id IN (%s)', $this->boundComponent->getCategories()
            ));
            $begin = (isset($this->filter_data['price']['begin'])) ? (int)$this->filter_data['price']['begin'] : $min;
            $end = (isset($this->filter_data['price']['end'])) ? (int)$this->filter_data['price']['end'] : $max;
            if ($begin < $min) $begin = $min;
            if ($end > $max) $end = $max;
            if (($min && $max && $begin && $end)) {
                $fd->setProperty('text-from', $this->translate('TXT_FROM'));
                $fd->setProperty('text-to', $this->translate('TXT_TO'));

                $fd->setProperty('range-min', (string)$min);
                $fd->setProperty('range-max', (string)$max);
                $fd->setProperty('range-begin', (string)$begin);
                $fd->setProperty('range-end', (string)$end);
                $fd->setProperty('range-step', 1);
            } elseif ($this->getParam('removeEmptyPriceFilter')) {
                $this->getDataDescription()->removeFieldDescription($fd);
            }

        }
    }

    protected function buildDivisionFilter() {
        if ($fd = $this->getDataDescription()->getFieldDescriptionByName('divisions')) {
            // todo: ничего не делаем, фильтр по разделам на совести xslt
            //$this -> getDataDescription() -> removeFieldDescription($fd);
        }
    }

    protected function buildFeatureFilter() {
        if ($fd = $this->getDataDescription()->getFieldDescriptionByName('features')) {

            // убираем field description, ибо это фейковое поле
            $this->getDataDescription()->removeFieldDescription($fd);

            // feature_ids текущего раздела
            $div_feature_ids = $this->dbh->getColumn(
                'shop_sitemap2features',
                'feature_id',
                [
                    'smap_id' => $this->document->getId()
                ]
            );

            // добавляем в форму только активные фичи, предназначенные для фильтра
            if ($div_feature_ids) {
                foreach ($div_feature_ids as $feature_id) {
                    $feature = FeatureFieldFactory::getField($feature_id);

                    if ($feature->isActive() and $feature->isFilter()) {
                        $filter_data = isset($this->filter_data['features'][$feature->getFilterFieldName()]) ? $this->filter_data['features'][$feature->getFilterFieldName()] : false;
                        $this->getDataDescription()->addFieldDescription($feature->getFilterFieldDescription($filter_data));
                        $this->getData()->addField($feature->getFilterField($filter_data));
                    }
                }
            }
        }
    }

    public function main() {
        $this->boundComponent = E()->getDocument()->componentManager->getBlockByName($this->getParam('bind'));
        if (!$this->getParam('showForProduct') && ($this->boundComponent->getState() == 'view')) {
            $this->disable();
            return;
        }

        $this->prepare();
        /**
         * @var GoodsList
         */
        $this->setProperty('action', substr(array_reduce($this->boundComponent->getSortData(), function ($p, $c) {
                return $p . $c . '-';
            }, 'sort-'), 0, -1) . '/');

        $this->filter_data = $this->boundComponent->getFilterData();

        $this->setProperty('applied', ($this->filter_data ? '1' : '0'));

        // если в конфиге задан фильтр по цене
        $this->buildPriceFilter();

        // если в конфиге задан фильтр по подразделам
        $this->buildDivisionFilter();

        // если в конфиге задан вывод фильтра по характеристикам
        $this->buildFeatureFilter();

        $this->buildProducersFilter();

    }

    protected function buildProducersFilter() {
        if ($fd = $this->getDataDescription()->getFieldDescriptionByName('producers')) {

            $producers = $this->dbh->getColumn('SELECT DISTINCT producer_id  FROM shop_goods WHERE smap_id IN (%s)', $this->boundComponent->getCategories());
            $fd->setType(FieldDescription::FIELD_TYPE_MULTI);
            $fd->setProperty('title', 'FILTER_PRODUCERS');
            if ($values = $this->dbh->select('SELECT p.producer_id, producer_name FROM shop_producers p LEFT JOIN shop_producers_translation pt ON(p.producer_id=pt.producer_id) AND (lang_id=%s) WHERE p.producer_id IN (%s)', $this->document->getLang(), $producers)) {
                $fd->loadAvailableValues($values, 'producer_id', 'producer_name');
                if (isset($this->filter_data['producers']) && !empty($this->filter_data['producers'])) {
                    $f = new Field('producers');
                    $f->setData([$this->filter_data['producers']], true);
                    $this->getData()->addField($f);

                }
            }
            else {
                $this->getDataDescription()->removeFieldDescription($fd);
            }
        }
    }

    public function build() {
        $this->setProperty('filter-name', self::FILTER_GET);
        foreach ($this->getDataDescription() as $fd) {
            $fd->setProperty('tableName', self::FILTER_GET);
        }
        $result = parent::build();

        return $result;
    }

}