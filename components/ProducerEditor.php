<?php

namespace Energine\shop\components;


use Energine\share\components\Grid;
use Energine\share\gears\SiteManager;

class ProducerEditor extends Grid {
    private $multishop = false;

    public function __construct($name, array $params = null) {
        parent::__construct($name, $params);
        $this->setTableName('shop_producers');
        $cols = array_keys($this->dbh->getColumnsInfo($this->getTableName()));
        //inspect($cols);
        if (in_array('producer_site_multi', $cols)) {
            $this->multishop = true;
        }
    }

    /**
     * Отбираем только те сайты которые являются магазинами
     *
     * @param string $fkTableName
     * @param string $fkKeyName
     * @return array
     */
    protected function getFKData($fkTableName, $fkKeyName) {
        $filter = [];
        if ($this->multishop && ($fkKeyName == 'site_id')) {
            //оставляем только те сайты где есть магазины
            if ($sites = E()->getSiteManager()->getSitesByTag('shop')) {
                $filter = array_map(function ($site) {
                    return $site->id;
                }, $sites);
                $filter['share_sites.site_id'] = $filter;
            }
        }

        if ($this->getState() !== self::DEFAULT_STATE_NAME) {
            $result =
                $this->dbh->getForeignKeyData($fkTableName, $fkKeyName, $this->document->getLang(), $filter);
        }

        return $result;
    }

    protected function getRawData() {
        if ($this->multishop && $this->document->getRights() != ACCESS_FULL) {
            //отбираем тех производителей права на которые есть у текущего пользователя
            //то есть те, у которых есть в перечен привязанных сайтов, сайты,

            //все магазины
            $sites = E()->getSiteManager()->getSitesByTag('shop');
            //ищем в них те разделы
        }
        parent::getRawData();
    }
}