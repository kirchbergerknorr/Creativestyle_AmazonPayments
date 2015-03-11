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
class Creativestyle_AmazonPayments_Block_Checkout_Js
    extends Creativestyle_AmazonPayments_Block_Checkout_Abstract
    implements Creativestyle_AmazonPayments_Block_Js_Interface {

    public function getAddressBookWidgetSize() {
        return $this->_getConfig()->getAddressBookWidgetSize();
    }

    public function getWalletWidgetSize() {
        return $this->_getConfig()->getWalletWidgetSize();
    }

    public function getQuoteBaseGrandTotal() {
        return (float)$this->_getQuote()->getBaseGrandTotal();
    }

    public function isResponsive() {
        return $this->_getConfig()->isResponsive();
    }

    public function getCallbackName() {
        return 'window.onAmazonPaymentsReady';
    }

}
