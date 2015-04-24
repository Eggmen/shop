<?php
/**
 * Created by PhpStorm.
 * User: pavka
 * Date: 4/24/15
 * Time: 11:12 AM
 */

namespace Energine\shop\gears;


use Energine\share\gears\SimpleBuilder;

class EmptyFormBuilder extends SimpleBuilder {
    protected function run() {
        parent::run();
        $this->getResult()->removeAttribute('empty');
    }
}