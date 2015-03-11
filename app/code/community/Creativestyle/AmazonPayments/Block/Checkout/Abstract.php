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
abstract class Creativestyle_AmazonPayments_Block_Checkout_Abstract extends Creativestyle_AmazonPayments_Block_Abstract {

    protected function _isActive() {
        if (($this->_getConfig()->isActive() & Creativestyle_AmazonPayments_Model_Config::PAY_WITH_AMAZON_ACTIVE)
            && $this->_getConfig()->isCurrentIpAllowed()
            && $this->_getConfig()->isCurrentLocaleAllowed()
            && $this->_isConnectionSecure())
        {
            $methodInstance = $this->isLive() ? Mage::getModel('amazonpayments/payment_advanced') : Mage::getModel('amazonpayments/payment_advanced_sandbox');
            return $methodInstance->isAvailable($this->_getQuote());
        }
        return false;
    }

    public function getOrderReferenceId() {
        return $this->_getCheckoutSession()->getOrderReferenceId();
    }

}
