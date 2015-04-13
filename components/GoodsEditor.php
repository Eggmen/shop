<?php

/**
 * @file GoodsEditor
 *
 * It contains the definition to:
 * @code class GoodsEditor; @endcode
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
use Energine\share\gears\Translit;
use Energine\share\gears\TreeBuilder;
use Energine\share\gears\TreeNode;
use Energine\share\gears\TreeNodeList;

/**
 * Goods editor.
 *
 * @code
 * class GoodsEditor;
 * @endcode
 */
class GoodsEditor extends Grid {

    /**
     * Division editor.
     * @var DivisionEditor $divisionEditor
     */
    protected $divisionEditor;

    /**
     * Relations editor.
     * @var GoodsRelationEditor $relationEditor
     */
    protected $relationEditor;

    /**
     * Feature editor.
     * @var GoodsFeatureEditor $featureEditor
     */
    protected $featureEditor;

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        $this->setTableName('shop_goods');
    }

    /**
     * Define additional parameter "selector"
     *
     * @return array
     */
    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'selector' => false,
            ]
        );
    }

    /**
     * Added "relations" and "features" data description to the forms
     *
     * @throws \Energine\share\gears\SystemException
     */
    protected function prepare() {

        parent::prepare();

        if (in_array($this->getState(), ['add', 'edit'])) {

            // relations
            $fd = new FieldDescription('relations');
            $fd->setType(FieldDescription::FIELD_TYPE_TAB);
            $fd->setProperty('title', $this->translate('TAB_GOODS_RELATIONS'));
            $this->getDataDescription()->addFieldDescription($fd);

            $field = new Field('relations');
            $state = $this->getState();
            $tab_url = (($state != 'add') ? $this->getData()->getFieldByName($this->getPK())->getRowData(0) : '') . '/relation/';

            $field->setData($tab_url, true);
            $this->getData()->addField($field);

            // features
            $fd = new FieldDescription('features');
            $fd->setType(FieldDescription::FIELD_TYPE_TAB);
            $fd->setProperty('title', $this->translate('TAB_GOODS_FEATURES'));
            $this->getDataDescription()->addFieldDescription($fd);

            $field = new Field('features');
            $state = $this->getState();
            $tab_url = (($state != 'add') ? $this->getData()->getFieldByName($this->getPK())->getRowData(0) : '') . '/feature/show/';

            $field->setData($tab_url, true);
            $this->getData()->addField($field);

        }
    }

    /**
     * Removed key from smap_id field
     *
     * @return array|mixed
     * @throws \Energine\share\gears\SystemException
     */
    protected function loadDataDescription() {
        $result = parent::loadDataDescription();
        if (in_array($this->getState(), ['add', 'edit'])) {
            $result['smap_id']['key'] = false;
        }
        return $result;
    }

    /**
     * @copydoc Grid::createDataDescription
     */
    protected function createDataDescription() {

        $result = parent::createDataDescription();

        if (in_array($this->getState(), ['add', 'edit'])) {
            // smap_id - as drop-down with only shop categories (to simplify)
            $fd = $result->getFieldDescriptionByName('smap_id');
            $fd->setType(FieldDescription::FIELD_TYPE_CUSTOM);
        }
        return $result;
    }

    protected function createData(){
        $result = parent::createData();

        if(in_array($this->getState(), ['add', 'edit'])){
            $site_id = E()->getSiteManager()->getSitesByTag('shop', true);

            if ($this->document->getRights() < ACCESS_FULL) {
                $site_id = array_intersect($site_id, $this->document->getUser()->getSites());
            }
            $root = new TreeNodeList();
            $da = [];
            foreach ($site_id as $siteID) {
                $map = E()->getMap($siteID);
                $siteRoot = $root->add(new TreeNode($siteID . '-0'));
                array_push($da, [
                    'id' => $siteID . '-0',
                    'name' =>E()->getSiteManager()->getSiteByID($siteID)->name,
                    'isLabel' => true,
                ]);
                foreach($map->getInfo() as $id=>$nodeData){
                    array_push($da, [
                        'id' => $id,
                        'name' => $nodeData['Name'],
                        'isLabel' => false,
                    ]);
                }
                $ids = $map->getPagesByTag('catalogue');
                foreach ($ids as $id) {
                    $siteRoot->addChild($map->getTree()->getNodeById($id));
                }

            }

            $dd = new DataDescription();
            $dd->load(
                [
                    'id' => [
                        'key' => true,
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_INT,
                        'length' => 10,
                        'index' => 'PRI'
                    ],
                    'name' => [
                        'nullable' => false,
                        'type' => FieldDescription::FIELD_TYPE_STRING,
                        'length' => 255,
                        'index' => false
                    ],
                    'isLabel' => [
                        'type' => FieldDescription::FIELD_TYPE_BOOL,
                    ],

                ]
            );
            $d = new Data();
            $d->load($da);
            //inspect($d);
            $b = new TreeBuilder();
            $b->setTree($root);
            $b->setDataDescription($dd);
            $b->setData($d);
            $b->build();
            $f = $result->getFieldByName('smap_id')->setData($b->getResult(), true);
        }

        return $result;
    }

    /**
     * @copydoc Grid::add
     */
    protected function add() {
        parent::add();
        $this->getData()->getFieldByName('goods_is_active')->setData(1, true);
    }

    /**
     * Build smap_name as extended info (with parent names)
     *
     * @throws \Energine\share\gears\SystemException
     */
    protected function edit() {
        parent::edit();

        $smapField = $this->getData()->getFieldByName('smap_id');
        for ($i = 0; $i < sizeof(E()->getLanguage()->getLanguages()); $i++) {
            //remove for 2.11.10
            $smapField->setRowProperty($i, 'smap_name', $this->dbh->getScalar(
                'SELECT CONCAT(site_name, ":", smap_name) as smap_name FROM share_sitemap sm LEFT JOIN share_sitemap_translation smt USING(smap_id) LEFT JOIN share_sites_translation s ON (s.site_id = sm.site_id) AND (s.lang_id = %s) WHERE sm.smap_id = %s AND smt.lang_id= %1$s', $this->document->getLang(), $smapField->getRowData(0)
            ));
        }
    }

    /**
     * Show division tree for form of adding/editing.
     */
    protected function showSmapSelector() {
        $this->request->shiftPath(1);
        $this->divisionEditor = ComponentManager::createBlockFromDescription(
            ComponentManager::getDescriptionFromFile('../core/modules/shop/templates/content/site_div_selector.container.xml'));
        $this->divisionEditor->run();
    }

    /**
     * Create component for editing relations to the goods.
     */
    protected function relationEditor() {
        $sp = $this->getStateParams(true);
        $params = ['config' => 'core/modules/shop/config/GoodsRelationEditor.component.xml'];

        if (isset($sp['goods_id'])) {
            $this->request->shiftPath(2);
            $params['goodsID'] = $sp['goods_id'];

        } else {
            $this->request->shiftPath(1);
        }
        $this->relationEditor = $this->document->componentManager->createComponent('relationEditor', 'Energine\shop\components\GoodsRelationEditor', $params);
        $this->relationEditor->run();
    }

    /**
     * Create component for editing features of the goods.
     */
    protected function featureEditor() {
        $sp = $this->getStateParams(true);
        $params = ['config' => 'core/modules/shop/config/GoodsFeatureEditor.component.xml'];

        if (isset($sp['goods_id'])) {
            $this->request->shiftPath(2);
            $params['goodsID'] = $sp['goods_id'];

        } else {
            $this->request->shiftPath(1);
        }
        $this->featureEditor = $this->document->componentManager->createComponent('featureEditor', 'Energine\shop\components\GoodsFeatureEditor', $params);
        $this->featureEditor->run();
    }

    /**
     * @copydoc GoodsEditor::build
     */
    public function build() {
        if ($this->getState() == 'showSmapSelector') {
            $result = $this->divisionEditor->build();
        } elseif ($this->getState() == 'relationEditor') {
            $result = $this->relationEditor->build();
        } elseif ($this->getState() == 'featureEditor') {
            $result = $this->featureEditor->build();
        } else {
            $result = parent::build();
        }
        return $result;
    }

    /**
     * @copydoc Grid::saveData
     */
    protected function saveData() {
        if (empty($_POST[$this->getTableName()]['goods_segment'])) {
            $_POST[$this->getTableName()]['goods_segment'] = Translit::asURLSegment($_POST[$this->getTranslationTableName()][E()->getLanguage()->getDefault()]['goods_name']);
        }

        $goodsID = parent::saveData();
        $this->saveRelations($goodsID);
        $this->saveFeatureValues($goodsID);
        return $goodsID;
    }

    /**
     * Link relations to the current goods_id (after save)
     *
     * @param int $goodsID
     * @throws \Energine\share\gears\SystemException
     */
    protected function saveRelations($goodsID) {
        $this->dbh->modify(
            'UPDATE shop_goods_relations
			SET session_id = NULL, goods_from_id=%s
			WHERE (goods_from_id IS NULL AND session_id=%s) or (goods_from_id = %1$s)',
            $goodsID, session_id()
        );
    }

    /**
     * Link feature values to the current goods_id (after save)
     * Also remove all incorrect values, not related to the selected goods category
     *
     * @param int $goodsID
     * @throws \Energine\share\gears\SystemException
     */
    protected function saveFeatureValues($goodsID) {
        $this->dbh->modify(
            'UPDATE shop_feature2good_values
			SET session_id = NULL, goods_id=%s
			WHERE (goods_id IS NULL AND session_id=%s) or (goods_id = %1$s)',
            $goodsID, session_id()
        );
        $smapID = $this->dbh->getScalar('shop_goods', 'smap_id', ['goods_id' => $goodsID]);
        // remove all incorrect feature values
        $this->dbh->modify(
            'DELETE FROM shop_feature2good_values
			WHERE goods_id=%s and feature_id NOT IN (
				SELECT feature_id from shop_sitemap2features where smap_id=%s)',
            $goodsID, $smapID
        );
    }
}