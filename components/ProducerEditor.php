<?php

namespace Energine\shop\components;


use Energine\share\components\Grid;

class ProducerEditor extends Grid {

	public function __construct($name,  array $params = null) {
		parent::__construct($name, $params);
        $this->setTableName('shop_producers');
	}
}