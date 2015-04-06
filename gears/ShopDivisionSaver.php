<?php
/**
 * @file
 * ShopDivisionSaver
 *
 * It contains the definition to:
 * @code
 * class ShopDivisionSaver;
 * @endcode
 *
 * @author andy.karpov
 *
 * @version 1.0.0
 */
namespace Energine\shop\gears;
use Energine\share\gears\DivisionSaver, Energine\share\gears\QAL;

/**
 * Saver for division editor.
 *
 * @code
 * class ShopDivisionSaver;
 * @endcode
 */
class ShopDivisionSaver extends DivisionSaver {

	/**
	 * Overloaded method to store sitemap features assigned to division
	 *
	 * @copydoc DivisionSaver::save
	 */
	public function save() {

		$result = parent::save();

		$smapID = ($this->getMode() ==
			QAL::INSERT) ? $result : $this->getData()->getFieldByName('smap_id')->getRowData(0);

		$features = (!empty($_POST['feature_id'])) ? $_POST['feature_id'] : array();

		//Удаляем все предыдущие записи в таблице связки фич с разделом
		$this->dbh->modify(
			QAL::DELETE,
			'shop_sitemap2features',
			null,
			array('smap_id' => $smapID)
		);

		// Записываем чистые данные поверху
		foreach ($features as $featureID) {
			if ($featureID) {
				$this->dbh->modify(
					QAL::INSERT,
					'shop_sitemap2features',
					array(
						'smap_id' => $smapID,
						'feature_id' => $featureID
					)
				);
			}
		}

		return $result;
	}
}