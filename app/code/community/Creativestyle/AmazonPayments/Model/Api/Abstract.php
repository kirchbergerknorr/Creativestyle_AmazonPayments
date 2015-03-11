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
abstract class Creativestyle_AmazonPayments_Model_Api_Abstract {

    protected $_api = null;
    protected $_store = null;

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public function getMerchantId() {
        return $this->_getConfig()->getMerchantId($this->_store);
    }

    public function setStore($store = null) {
        $this->_store = $store;
        return $this;
    }

}
