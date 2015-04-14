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
class FeatureEditor extends Grid {

    /**
     * Options editor.
     * @var FeatureOptionEditor $oEditor
     */
    private $oEditor;

    /**
     * @copydoc Grid::__construct
     */
    public function __construct($name,  array $params = null) {
        parent::__construct($name, $params);
        $this->setTableName('shop_features');
		$this->setOrder(array('group_id' => QAL::ASC, 'feature_name' => QAL::ASC));
    }

    protected function prepare() {

        parent::prepare();

        if (in_array($this->getState(), array('add', 'edit'))) {

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
        $params = array('config' => 'core/modules/shop/config/FeatureOptionEditor.component.xml');

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
        $featureID = parent::saveData();
        $this->dbh->modify(
			'UPDATE shop_feature_options
			SET session_id = NULL, feature_id=%s
			WHERE (feature_id IS NULL and session_id = %s) or (feature_id = %1$s)',
			$featureID, session_id()
		);
        return $featureID;
    }
}