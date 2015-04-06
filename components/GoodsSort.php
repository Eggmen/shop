<?php

namespace Energine\shop\components;
use Energine\share\components\DataSet;

class GoodsSort extends DataSet
{
	protected $sort_data = array();

	public function __construct($name, array $params = null)
	{
		parent::__construct($name, $params);
		$this->setParam('active', false);
		$this->setTitle($this->translate('TXT_SORT'));
	}

	protected function defineParams() {
		return array_merge(
			parent::defineParams(),
			array(
				'bind' => false,
			)
		);
	}

	protected function loadData() {
		return array($this -> sort_data);
	}

	public function main()
	{
		$goodsList = E() -> getDocument() -> componentManager -> getBlockByName($this -> getParam('bind'));
		$this -> sort_data = $goodsList -> getSortData();
		$this->prepare();
		$this -> setType(self::COMPONENT_TYPE_FORM);
	}
}