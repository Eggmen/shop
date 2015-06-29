<?php
/**
 * @file
 * FeatureEditor
 *
 * It contains the definition to:
 * @code
class FeatureEditor;
 * @endcode
 *
 * @author andy.karpov
 * @copyright Energine 2015
 *
 * @version 1.0.0
 */
namespace Energine\shop\components;

use Energine\share\components\Grid, Energine\share\gears\FieldDescription, Energine\share\gears\Field;
use Energine\share\gears\QAL;

/**
 * Feature editor.
 *
 * @code
 * class FeatureEditor;
 * @endcode
 */
class FeatureEditor extends Grid implements SampleFeatureEditor{

    /**
     * Options editor.
     * @var FeatureOptionEditor $oEditor
     */
    protected $oEditor;

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name, array $params = NULL) {
        parent::__construct($name, $params);
        $this->setTableName('shop_features');
        $this->setOrder(['group_id' => QAL::ASC, 'feature_name' => QAL::ASC]);
    }


    protected function defineParams() {
        return array_merge(
            parent::defineParams(),
            [
                'site' => false
            ]
        );
    }

    private function getSites() {
        $result = [];
        if ($siteID = $this->getParam('site')) {
            $result = [$siteID];
        } elseif ($this->document->getRights() < ACCESS_FULL) {
            $result = $this->document->getUser()->getSites();
            if (empty($result)) {
                $result = [0];
            }
        } else {
            foreach (E()->getSiteManager() as $site) {
                $result[] = $site->id;
            }
        }

        return $result;
    }

    protected function createDataDescription() {
        $r = parent::createDataDescription();
        if (in_array($this->getState(), ['add', 'edit'])) {
            $r->getFieldDescriptionByName('feature_smap_multi')->setProperty('tabName', 'TXT_CATEGORIES');
            if (($this->document->getRights() < ACCESS_FULL) && ($fd = $r->getFieldDescriptionByName('feature_site_multi'))) {
                $fd->setType(FieldDescription::FIELD_TYPE_HIDDEN);
            }
        }
        return $r;
    }

    /**
     * Отбираем только те сайты которые являются магазинами
     *
     * @param string $fkTableName
     * @param string $fkKeyName
     * @return array
     */
    protected function getFKData($fkTableName, $fkKeyName) {
        $filter = $result = [];
        if ($fkKeyName == 'site_id') {
            //оставляем только те сайты где есть магазины
            if ($sites = E()->getSiteManager()->getSitesByTag('shop')) {
                $filter['share_sites.site_id'] = array_map(function ($site) {
                    return (string)$site;
                }, $sites);
            }
        }
        if ($fkKeyName == 'smap_id') {
            //оставляем только те сайты где есть магазины
            if ($sites = E()->getSiteManager()->getSitesByTag('shop')) {
                $filter['share_sitemap.site_id'] = array_map(function ($site) {
                    return (string)$site;
                }, $sites);
            }
        }

        if ($this->getState() !== self::DEFAULT_STATE_NAME) {
            $result = $this->dbh->getForeignKeyData($fkTableName, $fkKeyName, $this->document->getLang(), $filter);
        }

        if (isset($result[0]) && ($fkKeyName == 'smap_id')) {
            $pages = $rootPages = [];
            foreach ($filter['share_sitemap.site_id'] as $siteID) {
                $map = E()->getMap($siteID);
                foreach ($map->getPagesByTag('catalogue') as $pageID) {
                    $pages[] = $pageID;
                    $pages = array_merge($pages, array_keys($map->getTree()->getNodeById($pageID)->asList()));
                    $rootPages[] = $pageID;
                }
            }

            $result[0] = array_filter($result[0], function ($row) use ($pages) {
                return in_array($row['smap_id'], $pages);
            });
            $result[0] = array_map(function ($row) use ($rootPages) {
                if (in_array($row['smap_id'], $rootPages)) $row['root'] = E()->getSiteManager()->getSiteByID($row['site_id'])->name;
                return $row;
            }, $result[0]);
        }
        return $result;
    }

    protected function prepare() {

        parent::prepare();

        if (in_array($this->getState(), ['add', 'edit'])) {

            $fd = new FieldDescription('options');
            $fd->setType(FieldDescription::FIELD_TYPE_TAB);
            $fd->setProperty('title', $this->translate('TAB_FEATURE_OPTIONS'));
            $this->getDataDescription()->addFieldDescription($fd);

            $field = new Field('options');
            $state = $this->getState();
            $tab_url = (($state != 'add') ? $this->getData()->getFieldByName($this->getPK())->getRowData(0) : '') . '/option/';

            $field->setData($tab_url, true);
            $this->getData()->addField($field);
        }
    }

    /**
     * Create component for editing options to the feature (type = OPTION / VARIANT).
     */
    protected function optionEditor() {
        $sp = $this->getStateParams(true);
        $params = ['config' => 'core/modules/shop/config/FeatureOptionEditor.component.xml'];

        if (isset($sp['feature_id'])) {
            $this->request->shiftPath(2);
            $params['featureID'] = $sp['feature_id'];

        } else {
            $this->request->shiftPath(1);
        }
        $this->oEditor = $this->document->componentManager->createComponent('oEditor', 'Energine\shop\components\FeatureOptionEditor', $params);
        $this->oEditor->run();
    }

    /**
     * @copydoc Grid::build
     */
    public function build() {
        if ($this->getState() == 'optionEditor') {
            $result = $this->oEditor->build();
        } else {
            $result = parent::build();
        }

        return $result;
    }

    /**
     * @copydoc Grid::saveData
     */
    // Привязывем все option с feature_id = NULL к текущей характеристике.
    protected function saveData() {
        //Для всех с не админскими правами принудительно выставляем в те сайты на которые у юзера есть права
        if (($this->document->getRights() < ACCESS_FULL)) {
            $_POST[$this->getTableName()]['feature_site_multi'] = $this->document->getUser()->getSites();
        }
        $featureID = parent::saveData();
        $this->dbh->modify(
            'UPDATE shop_feature_options
			SET session_id = NULL, feature_id=%s
			WHERE (feature_id IS NULL and session_id = %s) or (feature_id = %1$s)',
            $featureID, session_id()
        );
        return $featureID;
    }

    protected function getRawData() {

            //отбираем те фичи права на которые есть у текущего пользователя
            $this->addFilterCondition([$this->getTableName() . '.feature_id' => $this->dbh->getColumn('shop_features2sites', 'feature_id', ['site_id' => $this->getSites()])]);

        parent::getRawData();
    }
}

interface SampleFeatureEditor {

}