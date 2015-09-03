<?php
/**
 * Created by PhpStorm.
 * User: pavka
 * Date: 8/26/15
 * Time: 11:54 AM
 */

namespace Energine\shop\gears;


use Energine\share\gears\Data;
use Energine\share\gears\DataDescription;
use Energine\share\gears\DBWorker;
use Energine\share\gears\FieldDescription;
use Energine\share\gears\Primitive;

class Currency extends Primitive {
    use DBWorker;
    /**
     * @var array $data
     */
    private $data;
    /**
     * @var int
     */
    private $currentID = NULL;
    /**
     * @var array
     */
    private $map = [];

    /**
     * @throws \Energine\share\gears\SystemException
     */
    function __construct() {
        $this->data = $this->dbh->select('SELECT * FROM shop_currencies c LEFT JOIN shop_currencies_translation ct USING(currency_id) WHERE  currency_is_active AND lang_id = %s', E()->getLanguage()->getCurrent());
        if (empty($this->data)) throw new \InvalidArgumentException("ERR_NO_CURR_DATA");

        if (isset($_COOKIE['currency']) || E()->getSiteManager()->getCurrentSite()->CURRENCY) {
            $dirtyCurrency = (isset($_COOKIE['currency'])) ? $_COOKIE['currency'] : E()->getSiteManager()->getCurrentSite()->CURRENCY;
            $this->currentID = array_reduce($this->data, function ($carry, $row) use ($dirtyCurrency) {
                if ($row['currency_code'] == $dirtyCurrency) {
                    $carry = $row['currency_id'];
                }
                return $carry;
            });
        }

        if (!$this->currentID) {
            $this->currentID = array_reduce($this->data, function ($carry, $row) {
                if ($row['currency_is_default']) {
                    $carry = $row['currency_id'];
                }

                return $carry;
            });
        }

        if (!$this->currentID) {
            throw new \LogicException('ERR_NO_CURRENCY');
        }

        foreach ($this->data as $index => &$row) {
            $this->map[$row['currency_id']] = $index;
            $row['currency_is_current'] = ($this->currentID == $row['currency_id']);
        }
    }


    /**
     * Return Currency Object as Data object
     *
     * @return \Energine\share\gears\Data
     */
    public function asData() {
        $result = new Data();
        $result->load($this->data);
        return $result;
    }

    /**
     * @return \Energine\share\gears\DataDescription
     */
    public function asDataDescription() {
        $dd = new DataDescription();
        $str = $this->dbh->getColumnsInfo('shop_currencies');
        unset($str['currency_is_default'], $str['currency_is_active']);
        $dd->load($str);
        $dd->getFieldDescriptionByName('currency_shortname_order')->setType(FieldDescription::FIELD_TYPE_VALUE);
        $fd = new FieldDescription('currency_is_current');
        $fd->setType(FieldDescription::FIELD_TYPE_BOOL);
        $dd->addFieldDescription($fd);

        return $dd;
    }

    /**
     *
     * @param mixed $value
     * @param string $from
     * @param string $to
     *
     * @return mixed
     */
    public function convert($value, $from, $to = NULL) {
        if (is_null($to)) {
            $to = $this->currentID;
        }

        return $value;
    }

    /**
     * @param string $fmt format template, where currency fields are used
     * @param int $currID currency ID
     * @param mixed $value price
     * @throws \LogicException
     * @return string
     */
    public function format($fmt, $currID, $value) {
        if (!isset($this->map[$currID])) {
            throw new \LogicException($currID);
        }
        $text = $value;
        $data = $this->data[$this->map[$currID]];
        extract($data);
        $errorLevel = error_reporting(E_ERROR);
        $fmt = addslashes($fmt);
        eval("\$text = \"$fmt\";");
        error_reporting($errorLevel);

        return $text;
    }

    public function getInfo($currID = null) {
        if(is_null($currID)){
            $currID = $this->currentID;
        }

        return $this->data[$this->map[$currID]];
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    function __toString() {
        return (string)$this->currentID;
    }


}