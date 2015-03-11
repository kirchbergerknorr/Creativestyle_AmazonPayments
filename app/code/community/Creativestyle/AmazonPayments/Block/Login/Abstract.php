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
abstract class Creativestyle_AmazonPayments_Block_Login_Abstract extends Creativestyle_AmazonPayments_Block_Abstract {

    protected $_widgetHtmlIdPrefix = 'loginButtonWidget';

    protected function _isActive() {
        return ($this->_getConfig()->isActive() & Creativestyle_AmazonPayments_Model_Config::LOGIN_WITH_AMAZON_ACTIVE)
            && $this->_getConfig()->isCurrentIpAllowed()
            && $this->_getConfig()->isCurrentLocaleAllowed()
            && $this->_isConnectionSecure();
    }

}
