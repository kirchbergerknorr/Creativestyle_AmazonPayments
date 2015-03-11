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
class Creativestyle_AmazonPayments_Block_Login_Js
    extends Creativestyle_AmazonPayments_Block_Login_Abstract
    implements Creativestyle_AmazonPayments_Block_Js_Interface {

    public function getClientId() {
        return $this->_getConfig()->getClientId();
    }

    public function getCallbackName() {
        return 'window.onAmazonLoginReady';
    }

}
