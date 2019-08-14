<?php
class Orange35_SalesruleTime_Model_Rule_Rule extends Mage_Rule_Model_Rule {
    protected function _convertFlatToRecursive(array $data)
    {
        $arr = array();
        foreach ($data as $key => $value) {
            if (($key === 'conditions' || $key === 'actions') && is_array($value)) {
                foreach ($value as $id=>$data) {
                    $path = explode('--', $id);
                    $node =& $arr;
                    for ($i=0, $l=sizeof($path); $i<$l; $i++) {
                        if (!isset($node[$key][$path[$i]])) {
                            $node[$key][$path[$i]] = array();
                        }
                        $node =& $node[$key][$path[$i]];
                    }
                    foreach ($data as $k => $v) {
                        $node[$k] = $v;
                    }
                }
            } else {
                /**
                 * Convert dates into Zend_Date
                 */
                if (in_array($key, array('from_date', 'to_date')) && $value) {
                    $value = Mage::app()->getLocale()->date(
                        $value,
                        Varien_Date::DATETIME_INTERNAL_FORMAT,
                        null,
                        false
                    );
                }
                $this->setData($key, $value);
            }
        }

        return $arr;
    }
    
    public function validateData(Varien_Object $object)
    {
        $result = parent::validateData($object);
        $result = $result!= true ? $result : array();
        $fromDate = $toDate = null;

        if ($object->hasFromDate() && $object->hasToDate()) {
            $fromDate = $object->getFromDate();
            $toDate = $object->getToDate();
        }

        if ($fromDate && $toDate) {
            $fromDate = new Zend_Date($fromDate, Varien_Date::DATETIME_INTERNAL_FORMAT);
            $toDate   = new Zend_Date($toDate, Varien_Date::DATETIME_INTERNAL_FORMAT);

            if ($fromDate->compare($toDate) === 1) {
                if (!in_array(Mage::helper('rule')->__('End Date must be greater than Start Date.'), $result)){
                    $result[] = Mage::helper('rule')->__('End Date must be greater than Start Date.');
                }
            }
        }
        return !empty($result) ? $result : true;
    }
}