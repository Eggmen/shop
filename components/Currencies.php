<?php
/**
 * Created by PhpStorm.
 * User: pavka
 * Date: 8/25/15
 * Time: 6:09 PM
 */

namespace Energine\shop\components;


use Energine\share\components\DataSet;
use Energine\share\gears\Data;
use Energine\share\gears\DataDescription;
use Energine\share\gears\SimpleBuilder;

class Currencies extends DataSet {
    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'active' => false
            ]
        );
    }

    protected function main() {
        $dd = new DataDescription();
        $dd->load($this->dbh->getColumnsInfo('shop_currencies'));
        $this->setDataDescription($dd);

        $d = new Data();

        $d->load($this->dbh->select('shop_currencies', true, ['currency_is_active' => true]));
        $this->setData($d);
        $this->setBuilder(new SimpleBuilder());
    }
}