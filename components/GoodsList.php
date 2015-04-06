<?php

namespace Energine\shop\components;

use Energine\share\components\DBDataSet;
use Energine\share\gears\AttachmentManager;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\TagManager;
use Energine\share\gears\QAL;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;

class GoodsList extends DBDataSet {

	protected $div_feature_ids = array();
	protected $filter_data = array();

    public function __construct($name, array $params = null) {
        parent::__construct($name, $params);
        $this->setTableName('shop_goods');
        $this->setOrder(array('goods_price' => QAL::ASC));
		$this -> div_feature_ids = $this -> getDivisionFeatureIds();
		$this -> filter_data = $this -> getFilterData();
		$filter = $this -> getFilterWhereConditions();
		$this->setFilter($filter);
	}

	protected function getDivisionFeatureIds() {
		return $this->dbh->getColumn(
			'shop_sitemap2features',
			'feature_id',
			array('smap_id' => $this -> document -> getID())
		);
	}

	public function getFilterData() {

		$result = array();

		// если пришел сброс фильтра - удаляем его из сессии
		if (isset($_REQUEST['reset_filter'])) {
			unset($_SESSION['goods_filter'][$this -> document -> getID()]);
		}

		// если пришел признак применения фильтра - применяем
		elseif (isset($_REQUEST['apply_filter'])) {
			$_SESSION['goods_filter'][$this->document->getID()] = $_REQUEST['goods_filter'];
		}

		// если фильтр взведен
		if (!empty($_SESSION['goods_filter'][$this->document->getID()])) {

			$filter = $_SESSION['goods_filter'][$this -> document -> getID()];

			// price filter
			if (isset($filter['price'])) {
				$price_begin = (!empty($filter['price']['begin'])) ? (float) $filter['price']['begin'] : 0;
				$price_end = (!empty($filter['price']['end'])) ? (float) $filter['price']['end'] : 0;
				$result['price'] = array(
					'begin' => $price_begin,
					'end' => $price_end
				);
			}

			// features filter
			foreach ($this -> div_feature_ids as $feature_id) {

				$feature = FeatureFieldFactory::getField($feature_id);
				$feature_name = $feature -> getFilterFieldName();

				if (isset($filter[$feature_name])) {
					switch ($feature -> getFilterType()) {
						// range
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_RANGE:
							$begin = (!empty($filter[$feature_name]['begin'])) ? (float) $filter[$feature_name]['begin'] : 0;
							$end = (!empty($filter[$feature_name]['end'])) ? (float) $filter[$feature_name]['end'] : 0;
							$result['features'][$feature_name] = array(
								'feature' => $feature,
								'begin' => $begin,
								'end' => $end
							);
						break;
						// checkbox group (multiple values)
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_CHECKBOXGROUP:
							$selected_ids = (!empty($filter[$feature_name]) and is_array($filter[$feature_name])) ? $filter[$feature_name] : array();
							if (!empty($selected_ids)) {
								$result['features'][$feature_name] = array(
									'feature' => $feature,
									'values' => $selected_ids
								);
							}
						break;
						// radio group / select (single value)
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_RADIOGROUP:
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_SELECT:
							$selected_id = (!empty($filter[$feature_name])) ? $filter[$feature_name] : false;
							if (!empty($selected_id)) {
								$result['features'][$feature_name] = array(
									'feature' => $feature,
									'value' => $selected_id
								);
							}
						break;
						// todo: обработка остальных типы фильтров
					}
				}
			}
		}
		return $result;
	}

	protected function getFilterWhereConditions() {

		$result = array(
			'smap_id' => sprintf('(smap_id=%d)', $this->document->getId())
		);

		$filter_data = $this -> filter_data;
		if ($filter_data) {
			if (isset($filter_data['price'])) {
				$result['price'] = sprintf("(goods_price between %d and %d)", $filter_data['price']['begin'], $filter_data['price']['end']);
			}
			if (isset($filter_data['features'])) {
				foreach ($filter_data['features'] as $filter_feature) {

					$feature = $filter_feature['feature'];

					switch ($feature -> getFilterType() ) {

						// для диапазона ищем все goods_id, у которых опция (title) характеристики
						// попадает в выбранный диапазон float значений
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_RANGE:
							$option_ids = array();
							foreach ($feature -> getOptions() as $option_id => $option_data) {
								if ( (float)$option_data['value'] >= $filter_feature['begin']
								and   (float)$option_data['value'] <= $filter_feature['end'] ) {
									$option_ids[] = $option_id;
								}
							}
							$goods_ids = $this -> dbh -> getColumn(
								'select distinct g.goods_id
								from shop_goods g
								join shop_feature2good_values fv on g.goods_id = fv.goods_id and fv.feature_id = %s
								join shop_feature2good_values_translation fvt on fvt.fpv_id = fv.fpv_id and fvt.lang_id = %s
								where g.smap_id = %s and fvt.fpv_data in (%s)',
								$feature -> getFeatureId(),
								$this -> document -> getLang(),
								$this -> document -> getId(),
								$option_ids
							);

							if (empty($goods_ids)) {
								$goods_ids = array('-1');
							}

							$result[$feature -> getFilterFieldName()] =
								sprintf("(goods_id in (%s))", implode(',', $goods_ids));
						break;

						// множественный выбор (check box group)
						// находим все id-шки и ищем через FIND_IN_SET() каждую
						// на выходе получаем фильтр по goods_id
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_CHECKBOXGROUP:
							$option_ids = array();
							foreach ($feature -> getOptions() as $option_id => $option_data) {
								if (in_array($option_id, $filter_feature['values'])) {
									$option_ids[] = $option_id;
								}
							}

							if ($option_ids) {

								$where = [];
								foreach ($option_ids as $option_id) {
									$where[] = "FIND_IN_SET('$option_id', fvt.fpv_data)>0";
								}
								$where = ' AND (' . implode(' OR ', $where) . ')';

								$goods_ids = $this->dbh->getColumn(
									'select distinct g.goods_id
									from shop_goods g
									join shop_feature2good_values fv on g.goods_id = fv.goods_id and fv.feature_id = %s
									join shop_feature2good_values_translation fvt on fvt.fpv_id = fv.fpv_id and fvt.lang_id = %s
									where g.smap_id = %s ' . $where,
									$feature->getFeatureId(),
									$this->document->getLang(),
									$this->document->getId()
								);

								if (empty($goods_ids)) {
									$goods_ids = array('-1');
								}

								$result[$feature->getFilterFieldName()] =
									sprintf("(goods_id in (%s))", implode(',', $goods_ids));
							}
						break;

						// одиночный выбор значения (select или radio)
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_SELECT:
						case FeatureFieldAbstract::FEATURE_FILTER_TYPE_RADIOGROUP:
							$option_ids = array();
							foreach ($feature -> getOptions() as $option_id => $option_data) {
								if ($option_id == $filter_feature['value']) {
									$option_ids[] = $option_id;
								}
							}
							if ($option_ids) {
								$goods_ids = $this->dbh->getColumn(
									'select distinct g.goods_id
									from shop_goods g
									join shop_feature2good_values fv on g.goods_id = fv.goods_id and fv.feature_id = %s
									join shop_feature2good_values_translation fvt on fvt.fpv_id = fv.fpv_id and fvt.lang_id = %s
									where g.smap_id = %s and fvt.fpv_data in (%s)',
									$feature->getFeatureId(),
									$this->document->getLang(),
									$this->document->getId(),
									$option_ids
								);

								if (empty($goods_ids)) {
									$goods_ids = array('-1');
								}

								$result[$feature->getFilterFieldName()] =
									sprintf("(goods_id in (%s))", implode(',', $goods_ids));
							}
						break;
						// todo: обработка остальных типов фильтров
					}
				}
			}
		}
		return ($result) ? implode(' AND ', $result) : '';
	}

    protected function main() {

		parent::main();

		// attachments in list
		if ($this->getDataDescription()->getFieldDescriptionByName('attachments')) {
			$am = new AttachmentManager(
				$this->getDataDescription(),
				$this->getData(),
				$this->getTableName()
			);
			$am->createFieldDescription();
			if ($f = $this->getData()->getFieldByName('goods_id'))
				$am->createField('goods_id', true, $f->getData());
		}

		// tags in list
		if ($this->getDataDescription()->getFieldDescriptionByName('tags')) {
			$tm = new TagManager($this->getDataDescription(), $this->getData(), $this->getTableName());
			$tm->createFieldDescription();
			$tm->createField();
		}
	}
}