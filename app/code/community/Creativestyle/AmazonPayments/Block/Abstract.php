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
abstract class Creativestyle_AmazonPayments_Block_Abstract extends Mage_Core_Block_Template {

    /**
     * Instance of the current quote
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;

    protected $_widgetHtmlIdPrefix = null;

    protected $_widgetHtmlId = null;

    protected function _isActive() {
        return $this->_getConfig()->isActive();
    }

    protected function _isConnectionSecure() {
        if ($this->_getConfig()->isActive() & Creativestyle_AmazonPayments_Model_Config::LOGIN_WITH_AMAZON_ACTIVE) {
            if ($this->isPopup()) {
                return Mage::app()->getStore()->isCurrentlySecure();
            }
        }
        return true;
    }

    protected function _isOnepageCheckout() {
        $module = strtolower($this->getRequest()->getModuleName());
        $controller = strtolower($this->getRequest()->getControllerName());
        $action = strtolower($this->getRequest()->getActionName());
        if ($module == 'checkout' && $controller == 'onepage' && $action == 'index') {
            return true;
        }
        return false;
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    protected function _getQuote() {
        if (null === $this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    public function getCheckoutUrl() {
        return Mage::getUrl('amazonpayments/advanced_checkout');
    }

    public function getLoginRedirectUrl() {
        if ($this->_isOnepageCheckout()) {
            return $this->getPayRedirectUrl();
        }
        if ($this->isPopup()) {
            return Mage::getUrl('amazonpayments/advanced_login');
        }
        return Mage::getUrl('amazonpayments/advanced_login/redirect');
    }

    public function getPayRedirectUrl() {
        if ($this->isPopup()) {
            return Mage::getUrl('amazonpayments/advanced_login', array('target' => 'checkout'));
        }
        return Mage::getUrl('amazonpayments/advanced_login/redirect', array('target' => 'checkout'));
    }

    public function getMerchantId() {
        return $this->_getConfig()->getMerchantId();
    }

    public function getRegion() {
        return $this->_getConfig()->getRegion();
    }

    /**
     * @deprecated deprecated since 1.3.4
     */
    public function getEnvironment() {
        return $this->_getConfig()->getEnvironment();
    }

    public function getWidgetHtmlId() {
        if (null === $this->_widgetHtmlId) {
            if ($this->getIdSuffix()) {
                $this->_widgetHtmlId = $this->_widgetHtmlIdPrefix . ucfirst($this->getIdSuffix());
            } else {
                $this->_widgetHtmlId = uniqid($this->_widgetHtmlIdPrefix);
            }
        }
        return $this->_widgetHtmlId;
    }

    public function getWidgetClass() {
        return $this->_widgetHtmlIdPrefix;
    }

    public function isLoginActive() {
        return (bool)($this->_getConfig()->isActive() & Creativestyle_AmazonPayments_Model_Config::LOGIN_WITH_AMAZON_ACTIVE);
    }

    public function isLive() {
        return !$this->_getConfig()->isSandbox();
    }

    public function isVirtual() {
        return $this->_getQuote()->isVirtual();
    }

    /**
     * Render Amazon Payments block
     *
     * @return string
     */
    protected function _toHtml() {
        try {
            if ($this->_isActive()) {
                return parent::_toHtml();
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return '';
    }

    public function isPopup() {
        return $this->_getConfig()->isPopupAuthenticationExperience();
    }

}
