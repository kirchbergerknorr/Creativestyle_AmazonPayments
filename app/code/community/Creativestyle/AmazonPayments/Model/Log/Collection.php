<?php

/**
 * This file is part of the official Amazon Payments Advanced extension
 * for Magento (c) creativestyle GmbH <amazon@creativestyle.de>
 * All rights reserved
 *
 * Reuse or modification of this source code is not allowed
 * without written permission from creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  Copyright (c) 2014 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Log_Collection extends Varien_Data_Collection {

    protected $_logType = 'exception';

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _applyFilters($log) {
        if (!empty($this->_filters)) {
            foreach ($log as $field => $value) {
                if ($filter = $this->getFilter($field)) {
                    $filterValue = $filter->getValue();
                    if (isset($filterValue['like'])) {
                        if (strpos(strtolower($value), strtolower(trim((string)($filterValue['like']), ' %\''))) === false) {
                            return null;
                        }
                    }
                }
            }
        }
        return $log;
    }

    protected function _loadData() {
        if ($this->isLoaded()) {
            return $this;
        }

        $logFilePath = Creativestyle_AmazonPayments_Model_Logger::getAbsoluteLogFilePath($this->_logType);

        if (file_exists($logFilePath)) {

            $logArray = array();

            if (($fileHandle = fopen($logFilePath, 'r')) !== false) {
                $id = 0;
                $columnMapping = Creativestyle_AmazonPayments_Model_Logger::getColumnMapping($this->_logType);
                while (($row = fgetcsv($fileHandle, 0, $this->_getConfig()->getLogDelimiter(), $this->_getConfig()->getLogEnclosure())) !== false) {
                    $log = array('id' => ++$id);
                    foreach ($columnMapping as $index => $columnName) {
                        $log[$columnName] = isset($row[$index]) ? $row[$index] : '';
                    }
                    if ($log = $this->_applyFilters($log)) {
                        $logArray[] = new Varien_Object($log);
                    }
                }
            }

            if (!empty($logArray)) {
                krsort($logArray);
                $this->_totalRecords = count($logArray);
                $this->_setIsLoaded();
                $from = ($this->getCurPage() - 1) * $this->getPageSize();
                $to = $from + $this->getPageSize() - 1;
                $isPaginated = $this->getPageSize() > 0;
                $count = 0;
                foreach ($logArray as $log) {
                    $count++;
                    if ($isPaginated && ($count < $from || $count > $to)) {
                        continue;
                    }
                    $this->addItem($log);
                }
            }
        }

        return $this;
    }

    protected function _sortArray() {
        if (!empty($this->_items)) {
            krsort($this->_items);
        }
        return $this;
    }

    /**
     * @param mixed $attribute
     * @param mixed $condition
     */
    public function addAttributeToFilter($attribute, $condition = null) {
        $this->addFilter($attribute, $condition);
        return $this;
    }

    /**
     * @param mixed $attribute
     * @param mixed $condition
     */
    public function addFieldToFilter($attribute, $condition = null) {
        return $this->addAttributeToFilter($attribute, $condition);
    }

    public function loadData($printQuery = false, $logQuery = false) {
        return $this->_loadData();
    }

    public function setLogType($logType) {
        $this->_logType = $logType;
        return $this;
    }

}
