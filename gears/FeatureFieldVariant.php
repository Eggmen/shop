<?php

namespace Energine\shop\gears;

class FeatureFieldVariant extends FeatureFieldOption {

	public function __toString() {
		return (!empty($this -> value) and isset($this->options[$this->value]['value'])) ?
			(string) $this -> options[$this -> value]['value'] : '-';
	}
}