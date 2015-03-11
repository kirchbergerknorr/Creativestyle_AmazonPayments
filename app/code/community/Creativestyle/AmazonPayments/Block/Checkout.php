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
class Creativestyle_AmazonPayments_Block_Checkout extends Mage_Checkout_Block_Onepage_Abstract {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public function getOrderReferenceId() {
        return Mage::getSingleton('checkout/session')->getOrderReferenceId();
    }

    public function getMerchantId() {
        return $this->_getConfig()->getMerchantId();
    }

    public function showSandboxToolbox() {
        return $this->_getConfig()->showSandboxToolbox();
    }

    public function isLive() {
        return !$this->_getConfig()->isSandbox();
    }

    public function isVirtual() {
        return $this->getQuote()->isVirtual();
    }

    public function isResponsive() {
        return $this->_getConfig()->isResponsive();
    }

}
