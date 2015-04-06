<?php

namespace Energine\shop\components;
use Energine\share\components\Grid, Energine\share\gears\FieldDescription, Energine\share\gears\Field;
use Energine\share\gears\QAL;

class ProducerEditor extends Grid {

	public function __construct($name,  array $params = null) {
		parent::__construct($name, $params);
	}

	protected function applyUserSort() {
		$this->setOrder(array('producer_segment' => QAL::ASC));
	}


}