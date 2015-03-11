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
class Creativestyle_AmazonPayments_Block_Login_Button extends Creativestyle_AmazonPayments_Block_Login_Abstract {

    protected function _construct() {
        parent::_construct();
        if (!$this->hasData('template')) {
            $this->setTemplate('creativestyle/amazonpayments/login/button.phtml');
        }
    }

    protected function _isActive() {
        if ($this->_getCustomerSession()->isLoggedIn()
            && $this->_getCustomerSession()->getCustomer()->getAmazonUserId()) {
            return false;
        }
        return parent::_isActive();
    }

    public function isCustomDesignSet() {
        return $this->getData('button_type') || $this->getData('button_size') || $this->getData('button_color');
    }

}
