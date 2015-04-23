<?php
/**
 * @file
 * DivisionEditor
 *
 * It contains the definition to:
 * @code
 * class DivisionEditor;
 * @endcode
 *
 * @author dr.Pavka
 * @copyright Energine 2006
 *
 * @version 1.0.0
 */
namespace Energine\shop\components;

use  Energine\share\gears\QAL;

/**
 * Shop division editor.
 *
 * @code
 * class DivisionEditor;
 * @endcode
 *
 * @final
 */
class DivisionEditor extends \Energine\share\components\DivisionEditor {

    protected function getFKData($tableName, $keyName) {
        $result = [];
        if (($tableName == 'shop_features') && ($keyName == 'feature_id')) {
            // Для main убираем список значений в селекте, ни к чему он там
            if ($this->getState() !== self::DEFAULT_STATE_NAME) {
                $params = $this->getStateParams(true);
                if (isset($params['site_id'])) {
                    $siteID = $params['site_id'];
                } else {
                    $siteID = E()->getSiteManager()->getCurrentSite()->id;
                }
                $result = $this->dbh->getForeignKeyData($tableName, $keyName, $this->document->getLang(), ['shop_features.feature_is_active' => true, "shop_features.feature_id IN (SELECT feature_id FROM shop_features2sites where site_id=$siteID)"], ['shop_features.group_id' => QAL::ASC, 'shop_features_translation.feature_name' => QAL::ASC]);
            }
        } else {
            $result = parent::getFKData($tableName, $keyName);
        }


        return $result;
    }

    protected function createDataDescription() {
        $result = parent::createDataDescription();
        if (in_array($this->getState(), ['add', 'edit'])) {
            if ($fd = $result->getFieldDescriptionByName('smap_features_multi')) {
                $groupsData = E()->Utils->reindex($this->dbh->select('SELECT g.group_id, group_name FROM shop_feature_groups g LEFT JOIN shop_feature_groups_translation USING (group_id) WHERE group_is_active and lang_id = %s', $this->document->getLang()), 'group_id', true);
                foreach ($fd->getAvailableValues() as &$data) {
                    if(isset($groupsData[$data['attributes']['group_id']]))
                        $data['attributes']['group_name'] = $groupsData[$data['attributes']['group_id']]['group_name'];
                }
            }

        }
        return $result;
    }


    protected function showUserEditor() {
        $this->request->shiftPath(1);
        $this->userEditor =
            $this->document->componentManager->createComponent('userEditor', 'Energine\user\components\UserEditor', ['config' => 'core/modules/shop/config/UserEditor.component.xml']);
        $this->userEditor->run();
    }

    protected function showRoleEditor() {
        $this->request->shiftPath(1);
        $this->roleEditor =
            $this->document->componentManager->createComponent('roleEditor', 'Energine\shop\components\RoleEditor', ['config' => 'core/modules/user/config/RoleEditor.component.xml']);
        $this->roleEditor->run();
    }


}
