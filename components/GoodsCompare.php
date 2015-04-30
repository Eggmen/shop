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
        if ($this -> getState() == 'informer') {
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

        // data description для метода сравнения
        if ($this -> getState() == 'compare') {
            $data = new Data();
            $dataDescription = new DataDescription();
            $dataDescription->load(
                array(
                    'goods_id' => array(
                        'key' => true,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_INT,
                        'length' => 10,
                        'index' => 'PRI'
                    ),
                    'goods_name' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'goods_image' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'goods_price' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'goods_price_old' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ),
                    'features' => array(
                        'key' => false,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_CUSTOM,
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

        $this -> setProperty('state', 'informer');
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

        $this -> setProperty('state', 'informer');
        $this -> informer();
    }

    protected function clear()
    {
        if (!empty($_SESSION['goods_compare'])) {
            $_SESSION['goods_compare'] = [];
        }

        $this -> setProperty('state', 'informer');
        $this -> informer();
    }

    protected function compare()
    {
        $sp = $this -> getStateParams(true);
        $goods_ids = array_filter(explode(',', $sp['goodsIds']), 'is_numeric');
        //$goods_ids = (!empty($_REQUEST['goods_compare'])) ? $_SESSION['goods_compare'] : array();

        $this->prepare();
        $goods_table = $this -> getParam('goodsTableName');

        $res = $this -> dbh -> select(
            "SELECT g.goods_id, gt.goods_name, g.goods_price, g.goods_price_old,
             (select u.upl_path from share_uploads u join shop_goods_uploads gu on gu.upl_id = u.upl_id where gu.goods_id = g.goods_id LIMIT 1) as goods_image
             FROM {$goods_table} g
             LEFT JOIN shop_goods_translation gt on g.goods_id = gt.goods_id and gt.lang_id = %s
             WHERE g.goods_id in (%s)",
            $this -> document -> getLang(),
            $goods_ids
        );

        $d = new Data();
        $d -> load($res);
        $this -> setData($d);

        $field_goods_id = $d->getFieldByName('goods_id');
        $this -> buildFeatures($field_goods_id, $goods_ids);
    }

    protected function getGoodsDivisionFeatureIds($goods_ids) {
        $goods_table = $this -> getParam('goodsTableName');

        return $this->dbh->getColumn(
            "select DISTINCT sf.feature_id
            from shop_sitemap2features sf
            join {$goods_table} g on g.smap_id = sf.smap_id
            where g.goods_id in (%s)", $goods_ids
        );
    }

    /**
     * Метод построения поля features для списка сравнения
     *
     * @param Field $field_goods_id
     * @param array $goods_ids
     * @throws \SystemException
     */
    protected function buildFeatures($field_goods_id, $goods_ids) {
        if ($fd = $this->getDataDescription()->getFieldDescriptionByName('features')) {

            $fd->setType(FieldDescription::FIELD_TYPE_CUSTOM);

            $f = new Field('features');
            $this->getData()->addField($f);

            // получаем список фич разделов указанных товаров
            $features = $this -> getGoodsDivisionFeatureIds($goods_ids);

            // получаем список значений fpv_data для заданного массива goods_id
            $fpv_indexed = array();
            $fpv = $this->dbh->select(
                'select f.fpv_id, f.goods_id, f.feature_id, ft.fpv_data
				from shop_feature2good_values f
				left join shop_feature2good_values_translation ft
				on ft.fpv_id = f.fpv_id and ft.lang_id = %s
				where f.feature_id in (%s) and f.goods_id in (%s)',
                $this -> document -> getLang(),
                $features,
                $goods_ids
            );

            if ($fpv)
                foreach ($fpv as $row) {
                    $fpv_indexed[$row['goods_id']][$row['feature_id']] = $row;
                }

            // проходимся по всем данным, создаем каждую фичу через фабрику, передаем feature_id и fpv_data
            foreach ($field_goods_id->getData() as $key => $goods_id) {

                $feature_data = array();

                foreach ($features as $feature_id) {

                    $fpv_data = (isset($fpv_indexed[$goods_id][$feature_id]['fpv_data'])) ? $fpv_indexed[$goods_id][$feature_id]['fpv_data'] : '';
                    $feature = FeatureFieldFactory::getField($feature_id, $fpv_data);

                    if ($this -> list_features and !empty($this -> list_features)
                        and !in_array($feature->getSysName(), $this -> list_features))
                        continue;

                    $images = array();
                    if ($feature->getType() == FeatureFieldAbstract::FEATURE_TYPE_MULTIOPTION) {
                        $options = $feature->getOptions();
                        $values = $feature->getValue();
                        foreach ($values as $value) {
                            if (!empty($options[$value]['path'])) {
                                $images[$value] = $options[$value]['path'];
                            }
                        }
                    }

                    $feature_data[] = array(
                        'feature_id' => $feature -> getFeatureId(),
                        'feature_name' => $feature -> getName(),
                        'feature_title' => $feature -> getTitle(),
                        'feature_sysname' => $feature -> getSysName(),
                        'feature_type' => $feature->getType(),
                        'feature_value' => (string) $feature,
                        'group_id' => $feature -> getGroupId(),
                        'group_title' => $feature -> getGroupName(),
                        'feature_images' => $images
                    );
                }

                $builder = new SimpleBuilder();
                $localData = new Data();
                $localData->load($feature_data);

                $dataDescription = new DataDescription();
                $ffd =  new FieldDescription('feature_id');
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('group_title');
                $ffd->setType(FieldDescription::FIELD_TYPE_STRING);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('group_id');
                $ffd->setType(FieldDescription::FIELD_TYPE_INT);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('feature_title');
                $ffd->setType(FieldDescription::FIELD_TYPE_STRING);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('feature_sysname');
                $ffd->setType(FieldDescription::FIELD_TYPE_STRING);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('feature_type');
                $ffd->setType(FieldDescription::FIELD_TYPE_STRING);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('feature_value');
                $ffd->setType(FieldDescription::FIELD_TYPE_STRING);
                $dataDescription->addFieldDescription($ffd);

                $ffd = new FieldDescription('feature_images');
                $ffd->setType(FieldDescription::FIELD_TYPE_TEXTBOX_LIST);
                $dataDescription->addFieldDescription($ffd);

                $builder->setData($localData);
                $builder->setDataDescription($dataDescription);

                $builder->build();

                $f->setRowData($key, $builder->getResult());

            }
            // на выходе получаем строковые значения поля
        }
    }
}