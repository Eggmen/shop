<?php

namespace Energine\shop\components;
use Energine\share\components\DataSet;
use Energine\share\gears\FieldDescription;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;


class GoodsFilter extends DataSet
{
	protected $filter_data = array();

	public function __construct($name, array $params = null)
	{
		parent::__construct($name, $params);
		$this->setParam('active', false);
		$this->setTitle($this->translate('TXT_FILTER'));
	}

	protected function defineParams() {
		return array_merge(
			parent::defineParams(),
			array(
				'bind' => false,
				'tableName' => 'shop_goods'
			)
		);
	}

	protected function buildPriceFilter() {
		if ($fd = $this -> getDataDescription() -> getFieldDescriptionByName('price')) {

			$fd -> setType(FieldDescription::FIELD_TYPE_CUSTOM);
			$fd -> setProperty('title', $this -> translate('TXT_PRICE'));
			$fd -> setProperty('subtype', FeatureFieldAbstract::FEATURE_FILTER_TYPE_RANGE);

			/*$min = ceil( $this -> dbh -> getScalar(
				'select min(goods_price) from ' .
				$this -> getParam('tableName') .
				' where smap_id = %s', $this -> document -> getID()
			));*/
			$min = 0;
			$max = ceil( $this -> dbh -> getScalar(
				'select max(goods_price) from ' .
				$this -> getParam('tableName') .
				' where smap_id = %s', $this -> document -> getID()
			));
			$begin = (isset($this -> filter_data['price']['begin'])) ? (int) $this -> filter_data['price']['begin'] : $min;
			$end = (isset($this -> filter_data['price']['end'])) ? (int) $this -> filter_data['price']['end'] : $max;
			if ($begin < $min) $begin = $min;
			if ($end > $max) $end = $max;

			$fd -> setProperty('range-min', (string)$min);
			$fd -> setProperty('range-max', (string)$max);
			$fd -> setProperty('range-begin', (string)$begin);
			$fd -> setProperty('range-end', (string)$end);
			$fd -> setProperty('range-step', 1);
		}
	}

	protected function buildDivisionFilter() {
		if ($fd = $this -> getDataDescription() -> getFieldDescriptionByName('divisions')) {
			// todo: ничего не делаем, фильтр по разделам на совести xslt
			//$this -> getDataDescription() -> removeFieldDescription($fd);
		}
	}

	protected function buildFeatureFilter() {
		if ($fd = $this -> getDataDescription() -> getFieldDescriptionByName('features')) {

			// убираем field description, ибо это фейковое поле
			$this -> getDataDescription() -> removeFieldDescription($fd);

			// feature_ids текущего раздела
			$div_feature_ids = $this -> dbh -> getColumn(
				'shop_sitemap2features',
				'feature_id',
				array(
					'smap_id' => $this -> document -> getId()
				)
			);

			// добавляем в форму только активные фичи, предназначенные для фильтра
			if ($div_feature_ids) {
				foreach ($div_feature_ids as $feature_id) {
					$feature = FeatureFieldFactory::getField($feature_id);
					if ($feature->isActive() and $feature -> isFilter()) {
						$filter_data = isset($this -> filter_data['features'][$feature -> getFilterFieldName()]) ? $this -> filter_data['features'][$feature -> getFilterFieldName()] : false;
						$this->getDataDescription()->addFieldDescription($feature->getFilterFieldDescription($filter_data));
						$this->getData()->addField($feature->getFilterField($filter_data));
					}
				}
			}
		}
	}

	public function main()
	{
		$this->prepare();

		// получаем связанный с фильтром компонент
		$goodsList = E() -> getDocument() -> componentManager -> getBlockByName($this -> getParam('bind'));

		$this -> filter_data = $goodsList -> getFilterData();

		// если в конфиге задан фильтр по цене
		$this -> buildPriceFilter();

		// если в конфиге задан фильтр по подразделам
		$this -> buildDivisionFilter();

		// если в конфиге задан вывод фильтра по характеристикам
		$this -> buildFeatureFilter();

	}
}