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
	public function __construct($name,  array $params = null) {
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
			array(
				'selector' => false,
			)
		);
	}

	/**
	 * Added "relations" and "features" data description to the forms
	 *
	 * @throws \Energine\share\gears\SystemException
	 */
	protected function prepare() {

		parent::prepare();

		if (in_array($this->getState(), array('add', 'edit'))) {

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
		if (in_array($this->getState(), array('add', 'edit'))) {
			$result['smap_id']['key'] = false;
		}
		return $result;
	}

	/**
	 * @copydoc Grid::createDataDescription
	 */
	protected function createDataDescription() {

		$result = parent::createDataDescription();

		if (in_array($this->getState(), array('add', 'edit'))) {

			// smap_id - as drop-down with only shop categories (to simplify)
			$fd = $result->getFieldDescriptionByName('smap_id');
			$fd->setType(FieldDescription::FIELD_TYPE_SELECT);
			$fd->setAvailableValues(array());

			$site_id = E()->getConfigValue('shop.site_id');
			$catalog_id = E()->getConfigValue('shop.catalog_category_id');
			$map = E()->getMap($site_id);
			$par = $map->getTree()->getNodeById($catalog_id);
			$catalog_nodes = ($par) ? $par->getChildren()->asList(true) : array();
			$values = array();

			$buildCatalogItemName = function($info, Sitemap $map, $catalog_id) {
				$names = array($info['Name']);
				$pid = $info['Pid'];
				while ($pid != $catalog_id) {
					$node = $map->getDocumentInfo($pid);
					$pid = $node['Pid'];
					$names[] = $node['Name'];
				}
				return implode(' : ', array_reverse($names));
			};

			foreach ($catalog_nodes as $id => $node) {
				if (!$map->getTree()->getNodeById($id)->hasChildren()) {
					$info = $map->getDocumentInfo($id);
					$name = $buildCatalogItemName($info, $map, $catalog_id);
					$values[] = array(
						'key' => $id,
						'value' => $name
					);
				}
			}
			$fd->loadAvailableValues($values, 'key', 'value');
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
		$params = array('config' => 'core/modules/shop/config/GoodsRelationEditor.component.xml');

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
		$params = array('config' => 'core/modules/shop/config/GoodsFeatureEditor.component.xml');

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
		}
		elseif ($this->getState() == 'relationEditor') {
			$result = $this->relationEditor->build();
		}
		elseif ($this->getState() == 'featureEditor') {
			$result = $this->featureEditor->build();
		}
		else {
			$result = parent::build();
		}
		return $result;
	}

	/**
	 * @copydoc Grid::saveData
	 */
	protected function saveData() {
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
		$smapID = $this->dbh->getScalar('shop_goods', 'smap_id', array('goods_id' => $goodsID));
		// remove all incorrect feature values
		$this->dbh->modify(
			'DELETE FROM shop_feature2good_values
			WHERE goods_id=%s and feature_id NOT IN (
				SELECT feature_id from shop_sitemap2features where smap_id=%s)',
			$goodsID, $smapID
		);
	}
}