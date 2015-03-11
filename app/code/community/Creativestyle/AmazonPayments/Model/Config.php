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
class Creativestyle_AmazonPayments_Model_Config {

    const XML_PATH_ACCOUNT_MERCHANT_ID          = 'amazonpayments/account/merchant_id';
    const XML_PATH_ACCOUNT_ACCESS_KEY           = 'amazonpayments/account/access_key';
    const XML_PATH_ACCOUNT_SECRET_KEY           = 'amazonpayments/account/secret_key';
    const XML_PATH_ACCOUNT_REGION               = 'amazonpayments/account/region';

    const XML_PATH_GENERAL_ACTIVE               = 'amazonpayments/general/active';
    const XML_PATH_GENERAL_SANDBOX              = 'amazonpayments/general/sandbox';
    const XML_PATH_GENERAL_SANDBOX_TOOLBOX      = 'amazonpayments/general/sandbox_toolbox';
    const XML_PATH_GENERAL_PAYMENT_ACTION       = 'amazonpayments/general/payment_action';
    const XML_PATH_GENERAL_IPN_ACTIVE           = 'amazonpayments/general/ipn_active';
    const XML_PATH_GENERAL_ORDER_STATUS         = 'amazonpayments/general/authorized_order_status';
    const XML_PATH_GENERAL_RECENT_POLLED_TXN    = 'amazonpayments/general/recent_polled_transaction';

    const XML_PATH_LOGIN_ACTIVE                 = 'amazonpayments/login/active';
    const XML_PATH_LOGIN_CLIENT_ID              = 'amazonpayments/login/client_id';
    const XML_PATH_LOGIN_AUTHENTICATION         = 'amazonpayments/login/authentication';

    const XML_PATH_EMAIL_ORDER_CONFIRMATION     = 'amazonpayments/email/order_confirmation';
    const XML_PATH_EMAIL_DECLINED_TEMPLATE      = 'amazonpayments/email/authorization_declined_template';
    const XML_PATH_EMAIL_DECLINED_IDENTITY      = 'amazonpayments/email/authorization_declined_identity';

    const XML_PATH_DESIGN_BUTTON_SIZE           = 'amazonpayments/design_pay/button_size';
    const XML_PATH_DESIGN_BUTTON_COLOR          = 'amazonpayments/design_pay/button_color';

    const XML_PATH_DESIGN_RESPONSIVE            = 'amazonpayments/design/responsive';
    const XML_PATH_DESIGN_ADDRESS_WIDTH         = 'amazonpayments/design/address_width';
    const XML_PATH_DESIGN_ADDRESS_HEIGHT        = 'amazonpayments/design/address_height';
    const XML_PATH_DESIGN_PAYMENT_WIDTH         = 'amazonpayments/design/payment_width';
    const XML_PATH_DESIGN_PAYMENT_HEIGHT        = 'amazonpayments/design/payment_height';

    const XML_PATH_DESIGN_LOGIN_BUTTON_TYPE     = 'amazonpayments/design_login/login_button_type';
    const XML_PATH_DESIGN_LOGIN_BUTTON_SIZE     = 'amazonpayments/design_login/login_button_size';
    const XML_PATH_DESIGN_LOGIN_BUTTON_COLOR    = 'amazonpayments/design_login/login_button_color';
    const XML_PATH_DESIGN_PAY_BUTTON_TYPE       = 'amazonpayments/design_login/pay_button_type';
    const XML_PATH_DESIGN_PAY_BUTTON_SIZE       = 'amazonpayments/design_login/pay_button_size';
    const XML_PATH_DESIGN_PAY_BUTTON_COLOR      = 'amazonpayments/design_login/pay_button_color';

    const XML_PATH_DEVELOPER_ALLOWED_IPS        = 'amazonpayments/developer/allowed_ips';
    const XML_PATH_DEVELOPER_LOG_ACTIVE         = 'amazonpayments/developer/log_active';

    const PAY_WITH_AMAZON_ACTIVE                = 1;
    const LOGIN_WITH_AMAZON_ACTIVE              = 2;

    protected $_config = array();
    protected $_globalData = null;
    protected $_merchantValues = null;

    protected function _getConfig($store = null)  {
        if (!array_key_exists($store, $this->_config)) {
            $this->_config[$store] = array(
                'merchantId' => $this->getMerchantId($store),
                'accessKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_ACCESS_KEY, $store)),
                'secretKey' => trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_SECRET_KEY, $store)),
                'applicationName' => 'Creativestyle Amazon Payments Advanced Magento Extension',
                'applicationVersion' => Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version'),
                'region' => $this->getRegion($store),
                'environment' => $this->isSandbox($store) ? 'sandbox' : 'live',
                'serviceURL' => '',
                'widgetURL' => '',
                'caBundleFile' => '',
                'clientId' => ''
            );
        }
        return $this->_config[$store];
    }

    protected function _getGlobalData()  {
        if (null === $this->_globalData) {
            $this->_globalData = Mage::getConfig()->getNode('global/creativestyle/amazonpayments')->asArray();
        }
        return $this->_globalData;
    }

    public function getConnectionData($key = null, $store = null) {
        if (null !== $key) {
            $config = $this->_getConfig($store);
            if (array_key_exists($key, $config)) {
                return $config[$key];
            }
            return null;
        }
        return $this->_getConfig($store);
    }

    public function getGlobalDataValue($key = null) {
        if (null !== $key) {
            $data = $this->_getGlobalData();
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
            return null;
        }
        return $this->_getGlobalData();
    }

    public function getMerchantValues($store = null) {
        if (null === $this->_merchantValues) {
            $this->_merchantValues = new OffAmazonPaymentsService_MerchantValues(
                $this->getConnectionData('merchantId', $store),
                $this->getConnectionData('accessKey', $store),
                $this->getConnectionData('secretKey', $store),
                $this->getConnectionData('applicationName', $store),
                $this->getConnectionData('applicationVersion', $store),
                $this->getConnectionData('region', $store),
                $this->getConnectionData('environment', $store),
                $this->getConnectionData('serviceURL', $store),
                $this->getConnectionData('widgetURL', $store),
                $this->getConnectionData('caBundleFile', $store),
                $this->getConnectionData('clientId', $store)
            );
        }
        return $this->_merchantValues;
    }

    public function isActive($store = null) {
        $active = Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_ACTIVE, $store) ? self::PAY_WITH_AMAZON_ACTIVE : 0;
        $active |= Mage::getStoreConfigFlag(self::XML_PATH_LOGIN_ACTIVE, $store) ? self::LOGIN_WITH_AMAZON_ACTIVE : 0;
        return $active;
    }

    public function isIpnActive($store = null) {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_IPN_ACTIVE, $store);
    }

    public function isLoggingActive($store = null) {
        return Mage::getStoreConfigFlag(self::XML_PATH_DEVELOPER_LOG_ACTIVE, $store);
    }

    public function isSandbox($store = null) {
        return Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX, $store);
    }

    public function getMerchantId($store = null) {
        return trim(Mage::getStoreConfig(self::XML_PATH_ACCOUNT_MERCHANT_ID, $store));
    }

    public function getRegion($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_ACCOUNT_REGION, $store);
    }

    public function getEnvironment($store = null) {
        return $this->getConnectionData('environment', $store);
    }

    public function getClientId($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_LOGIN_CLIENT_ID, $store);
    }

    public function getAuthenticationExperience($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_LOGIN_AUTHENTICATION, $store);
    }

    public function isPopupAuthenticationExperience($store = null) {
        return $this->getAuthenticationExperience($store) == Creativestyle_AmazonPayments_Model_Lookup_Authentication::POPUP_EXPERIENCE;
    }

    public function getWidgetUrl($store = null) {
        if ($this->isActive() & self::LOGIN_WITH_AMAZON_ACTIVE) {
            return $this->getMerchantValues()->getWidgetUrl();
        } else if ($this->isActive()) {
            return str_replace('lpa/', '', $this->getMerchantValues()->getWidgetUrl());
        }
        return null;
    }

    public function getButtonUrl($store = null) {
        if (!($this->isActive() & self::LOGIN_WITH_AMAZON_ACTIVE)) {
            $buttonUrls = $this->getGlobalDataValue('button_urls');
            if (isset($buttonUrls[$this->getRegion($store)][$this->getEnvironment($store)])) {
                return sprintf($buttonUrls[$this->getRegion($store)][$this->getEnvironment($store)] . '?sellerId=%s&amp;size=%s&amp;color=%s',
                    $this->getMerchantId($store),
                    $this->getButtonSize($store),
                    $this->getButtonColor($store)
                );
            }
        }
        return null;
    }

    public function getLoginApiUrl($store = null) {
        $apiUrls = $this->getGlobalDataValue('login_api_urls');
        if (isset($apiUrls[$this->getRegion($store)][$this->getEnvironment($store)])) {
            return $apiUrls[$this->getRegion($store)][$this->getEnvironment($store)];
        }
        return '';
    }

    public function showSandboxToolbox($store = null) {
        return $this->isSandbox($store) && Mage::getStoreConfigFlag(self::XML_PATH_GENERAL_SANDBOX_TOOLBOX, $store);
    }


    public function getButtonType($store = null, $serviceType = null) {
        switch (strtolower($serviceType)) {
            case 'login':
                return Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_TYPE, $store);
            default:
                return Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_TYPE, $store);
        }
    }

    public function getButtonColor($store = null, $serviceType = null) {
        if ($this->isActive() & self::LOGIN_WITH_AMAZON_ACTIVE) {
            switch (strtolower($serviceType)) {
                case 'login':
                    return Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_COLOR, $store);
                default:
                    return Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_COLOR, $store);
            }
        }
        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_COLOR, $store);
    }

    public function getButtonSize($store = null, $serviceType = null) {
        if ($this->isActive() & self::LOGIN_WITH_AMAZON_ACTIVE) {
            switch (strtolower($serviceType)) {
                case 'login':
                    return Mage::getStoreConfig(self::XML_PATH_DESIGN_LOGIN_BUTTON_SIZE, $store);
                default:
                    return Mage::getStoreConfig(self::XML_PATH_DESIGN_PAY_BUTTON_SIZE, $store);
            }
        }
        return Mage::getStoreConfig(self::XML_PATH_DESIGN_BUTTON_SIZE, $store);
    }

    public function getAddressBookWidgetSize($store = null) {
        return new Varien_Object(array(
            'width' => Mage::getStoreConfig(self::XML_PATH_DESIGN_ADDRESS_WIDTH, $store) . 'px',
            'height' => Mage::getStoreConfig(self::XML_PATH_DESIGN_ADDRESS_HEIGHT, $store) . 'px'
        ));
    }

    public function getWalletWidgetSize($store = null) {
        return new Varien_Object(array(
            'width' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAYMENT_WIDTH, $store) . 'px',
            'height' => Mage::getStoreConfig(self::XML_PATH_DESIGN_PAYMENT_HEIGHT, $store) . 'px'
        ));
    }

    public function authorizeImmediately($store = null) {
        return in_array(Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION, $store), array(
            Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE,
            Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE
        ));
    }

    public function captureImmediately($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION, $store) == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE;
    }

    public function isManualAuthorizationAllowed($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION, $store) == Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_MANUAL;
    }

    public function isPaymentProcessingAllowed($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_PAYMENT_ACTION, $store) != Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_ERP;
    }

    public function getAuthorizedOrderStatus($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_ORDER_STATUS, $store);
    }

    public function sendEmailConfirmation($store = null) {
        return Mage::getStoreConfigFlag(self::XML_PATH_EMAIL_ORDER_CONFIRMATION, $store);
    }

    public function getAuthorizationDeclinedEmailTemplate($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_TEMPLATE, $store);
    }

    public function getAuthorizationDeclinedEmailIdentity($store = null) {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_DECLINED_IDENTITY, $store);
    }

    public function getLogDelimiter() {
        return ';';
    }

    public function getLogEnclosure() {
        return '"';
    }

    public function isCurrentIpAllowed($store = null) {
        $allowedIps = trim(Mage::getStoreConfig(self::XML_PATH_DEVELOPER_ALLOWED_IPS, $store), ' ,');
        if ($allowedIps) {
            $allowedIps = explode(',', str_replace(' ', '', $allowedIps));
            if (is_array($allowedIps) && !empty($allowedIps)) {
                $currentIp = Mage::helper('core/http')->getRemoteAddr();
                if (Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR')) {
                    $currentIp = Mage::app()->getRequest()->getServer('HTTP_X_FORWARDED_FOR');
                }
                return in_array($currentIp, $allowedIps);
            }
        }
        return true;
    }

    public function isCurrentLocaleAllowed($store = null) {
        $currentLocale = Mage::app()->getLocale()->getLocaleCode();
        $language = strtolower($currentLocale);
        if (strpos($language, '_')) {
            $language = substr($language, 0, strpos($language, '_'));
        }
        switch ($this->getRegion($store)) {
            case 'de':
                return ($language == 'de');
            case 'uk':
            case 'us':
                return ($language == 'en');
            default:
                return false;
        }
    }

    public function getRecentPolledTransaction() {
        return Mage::getStoreConfig(self::XML_PATH_GENERAL_RECENT_POLLED_TXN);
    }

    public function setRecentPolledTransaction($txnId) {
        Mage::getConfig()->saveConfig(self::XML_PATH_GENERAL_RECENT_POLLED_TXN, $txnId)->cleanCache();
    }

    public function isResponsive($store = null) {
        return Mage::getStoreConfigFlag(self::XML_PATH_DESIGN_RESPONSIVE, $store);
    }
}
