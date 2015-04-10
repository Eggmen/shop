<?php

namespace Energine\shop\components;

use Energine\share\components\DBDataSet;
use Energine\share\gears\AttachmentManager;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\TagManager;
use Energine\share\gears\QAL;
use Energine\share\gears\SystemException;
use Energine\shop\gears\FeatureFieldAbstract;
use Energine\shop\gears\FeatureFieldFactory;
use Energine\share\gears\Field;
use Energine\share\gears\Data;
use Energine\share\gears\DataDescription;
use Energine\share\gears\SimpleBuilder;

class GoodsList extends DBDataSet {

	/**
	 * Массив feature_id текущего раздела
	 *
	 * @var array
	 */
	protected $div_feature_ids = array();

	/**
	 * Массив с данными фильтров
	 *
	 * @var array
	 */
	protected $filter_data = array();

	/**
	 * Массив с данными для сортировки
	 *
	 * @var array
	 */
	protected $sort_data = array();

	/**
	 * Конструктор
	 *
	 * @param string $name
	 * @param array $params
	 */
    public function __construct($name, array $params = null) {

        parent::__construct($name, $params);

        $this->setTableName('shop_goods');
        $this->setOrder(array('goods_price' => QAL::ASC));
		$this -> div_feature_ids = $this -> getDivisionFeatureIds();

		$this -> filter_data = $this -> getFilterData();
		$filter = $this -> getFilterWhereConditions();
		$this->setFilter($filter);

		$this -> sort_data = $this -> getSortData();
		$sort = $this -> getSortConditions();
		$this->setOrder($sort);

	}

	/**
	 * Возвращает массив идентификаторов характеристик для данного раздела
	 *
	 * @return array
	 */
	protected function getDivisionFeatureIds() {

		return $this->dbh->getColumn(
			'shop_sitemap2features',
			'feature_id',
			array('smap_id' => $this -> document -> getID())
		);

	}

	/**
	 * Парсит и подготавливает поле и направление сортировки из request'а
	 * Сохраняет значения сортировки в сессии
	 *
	 * @return array
	 */
	public function getSortData() {
		if (isset($_REQUEST['sort'])) {
			$field = isset($_REQUEST['sort']['field']) ? $_REQUEST['sort']['field'] : 'price';
			if (!in_array($field, array('price', 'name'))) $field = 'price';
			$dir = isset($_REQUEST['sort']['dir']) ? $_REQUEST['sort']['dir'] : 'asc';
			if (!in_array($dir, array('asc', 'desc'))) $dir = 'asc';
			$_SESSION['goods_sort'] = array(
				'field' => $field,
				'dir' => $dir
			);
			return $_SESSION['goods_sort'];
		} elseif (isset($_SESSION['goods_sort'])) {
			return $_SESSION['goods_sort'];
		} else {
			return array(
				'field' => 'price',
				'dir' => 'asc'
			);
		}
	}

	/**
	 * Подготавливает условие сортировки датасета на основании внешних данных sort_data
	 *
	 * @return array
	 */
	protected function getSortConditions() {

		switch ($this -> sort_data['field']) {
			case 'price':
				$field = 'goods_price';
			break;
			case 'name':
				$field = 'goods_name';
			break;
			default:
				$field = 'goods_price';
		}

		switch ($this -> sort_data['dir']) {
			case 'asc':
				$dir = QAL::ASC;
			break;
			case 'desc':
				$dir = QAL::DESC;
			break;
			default:
				$dir = QAL::ASC;
		}

		return array($field => $dir);
	}

	/**
	 * Получает из request'а данные фильтра, сохраняет их в сессии
	 * для дальнейшего использования
	 * Возвращает массив фильтров (ключи price, features)
	 *
	 * @return array
	 */
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

	/**
	 * Получение значения WHERE для фильтра (внешняя фильтрация по цене / характеристикам)
	 *
	 * @return string
	 */
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

	/**
	 * Переопределенный метод вывода списка
	 * Выводит также аттачменты и теги для товаров
	 *
	 * @throws SystemException
	 */
    protected function main() {

		parent::main();

		// attachments in list
		$this -> buildAttachments();

		// tags in list
		$this -> buildTags();
	}

	/**
	 * Прикрепляет аттачменты к record'ам (если есть фейковое поле attachments в конфиге)
	 *
	 * @throws SystemException
	 */
	protected function buildAttachments() {
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
	}

	/**
	 * Прикрепляет теги к record'ам (если есть фейковое поле tags в конфиге)
	 *
	 * @throws SystemException
	 */
	protected function buildTags() {
		if ($this->getDataDescription()->getFieldDescriptionByName('tags')) {
			$tm = new TagManager($this->getDataDescription(), $this->getData(), $this->getTableName());
			$tm->createFieldDescription();
			$tm->createField();
		}
	}

	/**
	 * Переопределенный метод просмотра товара
	 *
	 * @throws SystemException
	 */
	protected function view() {

		$this->setType(self::COMPONENT_TYPE_FORM);

		$params = $this->getStateParams(true);
		$segment = $params['goodsSegment'];

		if (!($id = $this->recordExists($segment))) {
			throw new SystemException('ERR_404', SystemException::ERR_404);
		}

		$this->addFilterCondition([$this->getTableName() . '.' . $this->getPK() => $id]);

		//$this->document->componentManager->getBlockByName('breadCrumbs')->addCrumb('0001', '111', '222');

		$this->prepare();

		foreach ($this->getDataDescription() as $fieldDescription) {
			$fieldDescription->setMode(FieldDescription::FIELD_MODE_READ);
		}

		// attachments in view
		$this -> buildAttachments();

		// tags in view
		$this -> buildTags();

		// выводим фичи
		if ($fd = $this->getDataDescription()->getFieldDescriptionByName('features')) {

			$fd->setType(FieldDescription::FIELD_TYPE_CUSTOM);

			$f = new Field('features');
			$this->getData()->addField($f);

			// получаем список фич раздела
			$features = $this -> div_feature_ids;

			// получаем список значений fpv_data для заданного goods_id
			$fpv_indexed = array();
			$fpv = $this->dbh->select(
				'select f.fpv_id, f.goods_id, f.feature_id, ft.fpv_data
				from shop_feature2good_values f
				left join shop_feature2good_values_translation ft
				on ft.fpv_id = f.fpv_id and ft.lang_id = %s
				where f.feature_id in (%s) and f.goods_id = %s',
				$this -> document -> getLang(),
				$features,
				$id
			);

			if ($fpv)
				foreach ($fpv as $row) {
					$fpv_indexed[$row['goods_id']][$row['feature_id']] = $row;
				}

			$feature_data = array();

			foreach ($features as $feature_id) {

				$fpv_data = (isset($fpv_indexed[$id][$feature_id]['fpv_data'])) ? $fpv_indexed[$id][$feature_id]['fpv_data'] : '';
				$feature = FeatureFieldFactory::getField($feature_id, $fpv_data);

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
					'feature_images' => $images
				);
			}

			$builder = new SimpleBuilder();
			$localData = new Data();
			$localData->load($feature_data);

			$dataDescription = new DataDescription();
			$ffd =  new FieldDescription('feature_id');
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

			$f->setRowData(0, $builder->getResult());
			// на выходе получаем строковые значения поля
		}

		// выводим активные акции
		if ($fd = $this->getDataDescription()->getFieldDescriptionByName('promotions')) {

			$fd->setType(FieldDescription::FIELD_TYPE_CUSTOM);

			$f = new Field('promotions');
			$this->getData()->addField($f);

			$builder = new SimpleBuilder();
			$localData = new Data();

			$promotions_data = $this -> dbh -> select(
				'select p.promotion_id,
					p.promotion_start_date,
					p.promotion_end_date,
					pt.promotion_name,
					pt.promotion_description_rtf,
					DATEDIFF(p.promotion_end_date, NOW()) as days_left
				from shop_promotions p
				join shop_goods2promotions gp on p.promotion_id = gp.promotion_id and gp.goods_id = %s
				left join shop_promotions_translation pt on p.promotion_id = pt.promotion_id and pt.lang_id = %s
				where p.promotion_is_active = 1 and p.promotion_start_date <= NOW() and p.promotion_end_date >= NOW()',
				$id,
				$this -> document -> getLang()
			);
			if (!is_array($promotions_data)) $promotions_data = array();

			$localData->load($promotions_data);

			$dataDescription = new DataDescription();
			$ffd =  new FieldDescription('promotion_id');
			$dataDescription->addFieldDescription($ffd);

			$ffd = new FieldDescription('promotion_name');
			$ffd->setType(FieldDescription::FIELD_TYPE_STRING);
			$dataDescription->addFieldDescription($ffd);

			$ffd = new FieldDescription('promotion_description_rtf');
			$ffd->setType(FieldDescription::FIELD_TYPE_STRING);
			$dataDescription->addFieldDescription($ffd);

			$ffd = new FieldDescription('promotion_start_date');
			$ffd->setType(FieldDescription::FIELD_TYPE_DATE);
			$dataDescription->addFieldDescription($ffd);

			$ffd = new FieldDescription('promotion_end_date');
			$ffd->setType(FieldDescription::FIELD_TYPE_DATE);
			$dataDescription->addFieldDescription($ffd);

			$ffd = new FieldDescription('days_left');
			$ffd->setType(FieldDescription::FIELD_TYPE_INT);
			$dataDescription->addFieldDescription($ffd);

			$builder->setData($localData);
			$builder->setDataDescription($dataDescription);

			$builder->build();
			$f->setRowData(0, $builder->getResult());
		}
	}

	/**
	 * Переопределенный метод поиска записи товара по id или сегменту
	 *
	 * @param string $id идентификатор или сегмент товара
	 * @param string|bool $fieldName имя поля (по-умолчанию - PK)
	 * @return int|bool вовзращает id найденной записи или false
	 * @throws SystemException
	 */
	protected function recordExists($id, $fieldName = false) {

		// если не задан ID - в лес
		if (empty($id)) return false;

		// попытка получить запись по ID
		if (!$fieldName) $fieldName = $this->getPK();
		$res = $this->dbh->select($this->getTableName(), [$this->getPK()], [$fieldName => $id]);
		if ($res) return $res[0][$this->getPK()];

		if (empty($res)) {
			// попытка получить запись по сегменту
			$fieldName = 'goods_segment';
			$res = $this->dbh->select($this->getTableName(), [$this->getPK()], [$fieldName => $id]);
			if ($res) {
				return $res[0][$this->getPK()];
			}
		}

		// если не нашлось совпадений - беда
		return false;
	}

}