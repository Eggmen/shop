<?php
/**
 * @file
 * ShopDivisionEditor
 *
 * It contains the definition to:
 * @code
 * class ShopDivisionEditor;
 * @endcode
 *
 * @author dr.Pavka
 * @copyright Energine 2006
 *
 * @version 1.0.0
 */
namespace Energine\shop\components;

use Energine\share\components\DivisionEditor, Energine\share\gears, Energine\share\gears\FieldDescription, Energine\share\gears\JSONDivBuilder, Energine\share\gears\Data, Energine\share\gears\Builder, Energine\share\gears\Field, Energine\share\gears\DataDescription,  Energine\share\gears\Document, Energine\shop\gears\ShopDivisionSaver,Energine\share\gears\TagManager,  Energine\share\gears\SystemException, Energine\share\gears\JSONCustomBuilder, Energine\share\gears\QAL;

/**
 * Shop division editor.
 *
 * @code
 * class ShopDivisionEditor;
 * @endcode
 *
 * @final
 */
class ShopDivisionEditor extends DivisionEditor {

	/**
	 * Overloaded (copy-paste) just to change saver to ShopDivisionSaver
	 * TODO: move setSaver to another place as possible
	 *
	 * @copydoc Grid::save
	 */
	protected function save() {
		$this->setSaver(
			new ShopDivisionSaver()
		);
		$this->setBuilder(new JSONCustomBuilder());

		$transactionStarted = $this->dbh->beginTransaction();

		$result = $this->saveData();
		if (is_int($result)) {
			$mode = 'insert';
			$id = $result;
			/*Тут пришлось пойти на извращаения для получения УРЛа страницы, поскольку новосозданная страница еще не присоединена к дереву*/
			//$smapPID = simplifyDBResult($this->dbh->select('share_sitemap', 'smap_pid', array('smap_id'=>$id)), 'smap_pid', true);
			$smapPID =
				$this->getSaver()->getData()->getFieldByName('smap_pid')->getRowData(0);
			$url = $_POST[$this->getTableName()]['smap_segment'] . '/';
			if ($smapPID) {
				$url = E()->getMap(
						E()->getSiteManager()->getSiteByPage($smapPID)->id
					)->getURLByID($smapPID) . $url;
			}
		} else {
			$mode = 'update';
			$id = $this->getFilter();
			$id = $id['smap_id'];
			$url =
				E()->getMap(E()->getSiteManager()->getSiteByPage($id)->id)->getURLByID($id);
		}

		//Ads
		//        $ads = new AdsManager($result, $this->getState());
		//        $adsID = $ads->save();


		$transactionStarted = !($this->dbh->commit());
		$b = $this->getBuilder();
		$b->setProperty('result', true)->setProperty('mode', $mode)->setProperty('url', $url);
	}

	/**
	 * Overloaded method to create a new field feature_id inside a new TAB with list of all available features
	 *
	 * @return DataDescription
	 * @throws SystemException
	 */
	protected function createDataDescription() {

		$result = parent::createDataDescription();

		if (in_array($this->getState(), array('add', 'edit'))) {

			$fd = new FieldDescription('feature_id');
			$fd->setSystemType(FieldDescription::FIELD_TYPE_INT);
			$fd->setType(FieldDescription::FIELD_TYPE_MULTI);
			$fd->setProperty('tabName', 'TXT_FEATURES');
			$fd->setProperty('customField', true);

			$lang_id = $this->document->getLang();

			$data = $this->dbh->select(
				'select f.feature_id, ft.feature_name
				from shop_features f
				left join shop_features_translation ft
				on f.feature_id = ft.feature_id and ft.lang_id = %s
				where f.feature_is_active = 1
				order by ft.feature_name asc',
				$lang_id
			);

			$fd->loadAvailableValues($data, 'feature_id', 'feature_name');
			$result->addFieldDescription($fd);
		}

		return $result;
	}

	/**
	 * Overloaded method to set existing data for the feature_id field
	 *
	 * @return Data
	 * @throws SystemException
	 * @throws \SystemException
	 */
	protected function createData() {
		$result = parent::createData();
		$id = $this->getFilter();
		$id = (!empty($id) && is_array($id)) ? current($id) : false;
		if ($this->getType() != self::COMPONENT_TYPE_LIST && $id) {

			$f = new Field('feature_id');
			$result->addField($f);

			$data = $this->dbh->select('shop_sitemap2features', array('feature_id'), array('smap_id' => $id));
			if ($data) {
				$f->addRowData(array_keys(convertDBResult($data, 'feature_id', true)));
			} else {
				$f->setData(array());
			}
		}
		return $result;
	}

	protected function showUserEditor() {
		$this->request->shiftPath(1);
		$this->userEditor =
			$this->document->componentManager->createComponent('userEditor', 'Energine\shop\components\UserEditor', array('config' => 'core/modules/shop/config/UserEditor.component.xml'));
		$this->userEditor->run();
	}


}
