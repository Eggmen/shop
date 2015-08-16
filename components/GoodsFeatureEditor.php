<?php
/**
 * @file
 * GoodsFeatureEditor
 *
 * It contains the definition to:
 * @code
 * class GoodsFeatureEditor;
 * @endcode
 *
 * @author andy.karpov
 * @copyright Energine 2015
 *
 * @version 1.0.0
 */
namespace Energine\shop\components;

use Energine\share\components\Grid, Energine\share\gears\QAL;
use Energine\share\gears\DataDescription;
use Energine\share\gears\Field;
use Energine\share\gears\FieldDescription;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;
use Energine\shop\gears\FeatureFieldMultioption;

/**
 * Goods feature editor editor.
 *
 * @code
 * class GoodsFeatureEditor;
 * @endcode
 */
class GoodsFeatureEditor extends Grid {

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        $this->setTableName('shop_feature2good_values');
    }

    /**
     * @copydoc Grid::defineParams
     */
    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'goodsID' => false,
                'smapID' => false,
            ]
        );
    }


    /**
     * @copydoc Grid::loadDataDescription
     *
     */
    protected function loadDataDescription() {
        $result = parent::loadDataDescription();
        if (in_array($this->getState(), ['add', 'edit', 'save'])) {
            unset($result['goods_id']);
        }
        return $result;
    }

    /**
     * Для списка устанавливает фильтр по goods_id и feature_id (в привязке к текущему smap_id, переданному в сессии)
     * Также для характеристик типа OPTION, VARIANT тянет значение FK выбранной опции
     *
     * @return array|bool|false|mixed
     * @throws \Energine\share\gears\SystemException
     */
    protected function loadData() {
        if ($this->getState() == 'getRawData') {

            $features = (!empty($_SESSION['goods_feature_editor']['filter_feature_id'])) ? $_SESSION['goods_feature_editor']['filter_feature_id'] : ['-1'];
            $goodsID = (!empty($_SESSION['goods_feature_editor']['filter_goods_id'])) ? $_SESSION['goods_feature_editor']['filter_goods_id'] : NULL;

            if ($goodsID) {
                $filter = '(goods_id =' . $goodsID . ')';
            } else {
                $filter = '(goods_id IS NULL and session_id="'. session_id() .'")';
            }
            $filter = '((' . $filter . ') AND feature_id IN (' . join(',', $features) . '))';
            $this->setFilter($filter);
        }
        $data = parent::loadData();

        if ($data and is_array($data) and $this->getState() == 'getRawData') {

            foreach ($data as $key => $row) {

                // замена строкового значения характеристики для списка
                $feature = FeatureFieldFactory::getField($row['feature_id'], $row['fpv_data']);
                if ($feature) {
                    $data[$key]['fpv_data'] = (string)$feature;
                }

            }
        }

        if ($data and is_array($data) and $this->getState() == 'save') {
            $feature = FeatureFieldFactory::getField($data[0]['feature_id']);
            if ($feature and $feature->getType() == FeatureFieldAbstract::FEATURE_TYPE_MULTIOPTION or
				$feature and $feature->getType() == FeatureFieldAbstract::FEATURE_TYPE_VARIANT) {
                foreach ($data as $idx => $row) {
                    if (isset($row['fpv_data']) and is_array($row['fpv_data'])) {
                        $data[$idx]['fpv_data'] = implode(',', $data[$idx]['fpv_data']);
                    }
                }
            }
            $langs = array_keys(E()->getLanguage()->getLanguages());
            if (($count = sizeof($langs) - sizeof($data)) != 0) {
                foreach ($langs as $idx => $langID) {
                    if (!isset($data[$idx])) {
                        $data[$idx] = $data[0];
                        $data[$idx]['lang_id'] = $langID;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * При инициализации редактора (активации вкладки в родительском редакторе разделов)
     * создает пустые значения характеристик в привязке к товару.
     * Также передает в сессионные переменные для метода getRawData выбранные фильтры по smapID / goodsID
     *
     * @throws \Energine\share\gears\SystemException
     */
    protected function main() {
        parent::main();

        $params = $this->getStateParams(true);
        $smapID = ($params) ? $params['smap_id'] : '';

        $goodsID = $this->getParam('goodsID');
        $goodsID = (!empty($goodsID)) ? $goodsID : '';

        $this->setProperty('smap_id', $smapID);
        $this->setProperty('goods_id', $goodsID);

        $languages = E()->getLanguage()->getLanguages();
        $features = [];

        if ($smapID) {
            $features = $this->dbh->getColumn('shop_sitemap2features', 'feature_id', ['smap_id' => $smapID]);
            if ($features) {
                foreach ($features as $feature_id) {
                    if ($goodsID) {
                        $fpv_id = $this->dbh->getScalar('shop_feature2good_values', 'fpv_id', ['feature_id' => $feature_id, 'goods_id' => $goodsID]);
                    } else {
                        $fpv_id = $this->dbh->getScalar('shop_feature2good_values', 'fpv_id', ['feature_id' => $feature_id, 'session_id' => session_id()]);
                    }
                    if (!$fpv_id) {
                        $fpv_id = $this->dbh->modify(
                            QAL::INSERT_IGNORE, 'shop_feature2good_values',
                            ['feature_id' => $feature_id, 'goods_id' => $goodsID, 'session_id' => session_id()]
                        );
                        if ($fpv_id) {
                            foreach ($languages as $lang_id => $language) {
                                $this->dbh->modify(
                                    QAL::INSERT_IGNORE,
                                    'shop_feature2good_values_translation',
                                    ['fpv_id' => $fpv_id, 'lang_id' => $lang_id]);
                            }
                        }
                    }
                }
            }
        }

        if (empty($features)) {
            $features = ['-1'];
        }

        // устанавливаем сессионный фильтр, который передаем в getRawData
        $_SESSION['goods_feature_editor']['filter_feature_id'] = $features;
        $_SESSION['goods_feature_editor']['filter_smap_id'] = $smapID;
        $_SESSION['goods_feature_editor']['filter_goods_id'] = $goodsID;
    }

    /**
     * Форма редактирования меняет тип поля fpv_data в зависимости от типа характеристики
     * (OPTION, BOOL, INT, STRING, ...)
     * Для типа поля OPTION также наполняет значения выпадающего списка
     *
     * @throws \Energine\share\gears\SystemException
     */
    protected function edit() {
        parent::edit();


        $data = $this->getData();
        $dd = $this->getDataDescription();
        $fd = $dd->getFieldDescriptionByName('fpv_data');

        $feature_id = $data->getFieldByName('feature_id')->getRowData(0);
        $fpv_data = $data->getFieldByName('fpv_data')->getRowData(0);

        $feature = FeatureFieldFactory::getField($feature_id, $fpv_data);
        if ($feature) {
            $feature->modifyFormFieldDescription($dd, $fd);
            $field = $data->getFieldByName('fpv_data');
            $feature->modifyFormField($field);

            $fd = new FieldDescription('feature_name');
            $fd->setType(FieldDescription::FIELD_TYPE_STRING);
            $fd->setMode(FieldDescription::FIELD_MODE_READ);
            $fd->setProperty('tabName', E()->getLanguage()->getNameByID(E()->getLanguage()->getCurrent()));
            $dd->addFieldDescription($fd, DataDescription::FIELD_POSITION_AFTER, 'feature_id');

            $f= new Field('feature_name');
            $f->setData(($feature->getTitle())?$feature->getTitle():$feature->getName(), true);
            $data->addField($f);

        } else {
            // unknown feature type
            $fd->setType(FieldDescription::FIELD_TYPE_HIDDEN);
        }
    }


    public function build() {
        $result = parent::build();
        if (in_array($this->getDataDescription()->getFieldDescriptionByName('fpv_data')->getType(), [FieldDescription::FIELD_TYPE_MULTI, FieldDescription::FIELD_TYPE_SELECT, FieldDescription::FIELD_TYPE_INT, FieldDescription::FIELD_TYPE_BOOL])) {
            $xp = new \DOMXPath($result);
            if ($nodes = $xp->query('//field[@name="fpv_data" and @language!=' . $this->document->getLang() . ']')) {
                foreach ($nodes as $node) {
                    $node->parentNode->removeChild($node);
                }
            }
        }

        return $result;
    }

}