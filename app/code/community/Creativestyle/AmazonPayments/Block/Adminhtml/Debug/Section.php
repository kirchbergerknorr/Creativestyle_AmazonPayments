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
class Creativestyle_AmazonPayments_Block_Adminhtml_Debug_Section extends Mage_Adminhtml_Block_Template {

    protected $_id = null;

    protected $_debugArea = 'general';

    protected $_showKeys = true;

    public function __construct() {
        parent::__construct();
        $this->setTemplate('creativestyle/amazonpayments/debug/section.phtml');
    }

    public function getDebugData() {
        return Mage::helper('amazonpayments/debug')->getDebugData($this->_debugArea);
    }

    public function getSectionId() {
        if (null === $this->_id) {
            $this->_id = 'amazon-payments-debug-section-' . uniqid();
        }
        return $this->_id;
    }

    public function setDebugArea($debugArea) {
        $this->_debugArea = $debugArea;
        return $this;
    }

    public function setShowKeys($showKeys) {
        $this->_showKeys = (bool)$showKeys;
        return $this;
    }

    public function showKeys() {
        return $this->_showKeys;
    }

    public function formatOutput($value) {
        if (false === $value) return 'No';
        if (true === $value) return 'Yes';
        return $value;
    }
}
