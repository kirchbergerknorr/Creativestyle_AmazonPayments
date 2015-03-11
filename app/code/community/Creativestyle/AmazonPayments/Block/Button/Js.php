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
class Creativestyle_AmazonPayments_Block_Button_Js
    extends Creativestyle_AmazonPayments_Block_Abstract
    implements Creativestyle_AmazonPayments_Block_Js_Interface {

    public function getUrlParams() {
        if ($this->isLoginActive()) {
            $params = array(
                'login' => $this->getLoginRedirectUrl(),
                'pay' => $this->getPayRedirectUrl()
            );
        } else {
            $params = array(
                'checkout' => $this->getCheckoutUrl()
            );
        }
        return $this->helper('core')->jsonEncode($params);
    }

    public function getDesignParams() {
        if ($this->isLoginActive()) {
            $params = array(
                'payButton' => array(
                    'type' => $this->_getConfig()->getButtonType(),
                    'size' => $this->_getConfig()->getButtonSize(),
                    'color' => $this->_getConfig()->getButtonColor()
                ),
                'loginButton' => array(
                    'type' => $this->_getConfig()->getButtonType(null, 'login'),
                    'size' => $this->_getConfig()->getButtonSize(null, 'login'),
                    'color' => $this->_getConfig()->getButtonColor(null, 'login')
                )
            );
            return $this->helper('core')->jsonEncode($params);
        }
        return '{}';
    }

    public function getCallbackName() {
        return 'window.onAmazonPaymentsReady';
    }

}
