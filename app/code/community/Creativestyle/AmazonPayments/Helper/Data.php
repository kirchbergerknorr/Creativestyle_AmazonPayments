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

/**
 * Amazon Payments data helper
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 */
class Creativestyle_AmazonPayments_Helper_Data extends Mage_Core_Helper_Abstract {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Sends an email to the customer if authorization has been declined
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Creativestyle_AmazonPayments_Helper_Data
     */
    public function sendAuthorizationDeclinedEmail($order) {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $mailTemplate = Mage::getModel('core/email_template');

        $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStore()->getId()))
            ->sendTransactional(
                $this->_getConfig()->getAuthorizationDeclinedEmailTemplate($order->getStore()->getId()),
                $this->_getConfig()->getAuthorizationDeclinedEmailIdentity($order->getStore()->getId()),
                $order->getCustomerEmail(),
                null,
                array(
                    'orderId' => $order->getIncrementId(),
                    'storeName' => $order->getStore()->getFrontendName(),
                    'customer' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
                )
            );
        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Return array of all available Amazon payment methods
     *
     * @return array
     */
    public function getAvailablePaymentMethods() {
        return array(
            'amazonpayments_advanced',
            'amazonpayments_advanced_sandbox'
        );
    }

    /**
     * @deprecated deprecated since 1.2.6
     *
     * Check if the current User Agent is specific for any mobile device
     *
     * @return bool
     */
    public function isMobileDevice() {
        $userAgent = Mage::app()->getRequest()->getServer('HTTP_USER_AGENT');
        if (empty($userAgent)) {
            return false;
        }
        return preg_match('/iPhone|iPod|BlackBerry|Palm|Googlebot-Mobile|Mobile|mobile|mobi|Windows Mobile|Safari Mobile|Android|Opera Mini/', $userAgent);
    }

    /**
     * @deprecated deprecated since 1.6.2
     */
    public function getTransactionStatus($transaction) {
        $statusArray = $transaction->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        if (is_array($statusArray) && array_key_exists('State', $statusArray)) {
            return $statusArray['State'];
        }
        return null;
    }

    /**
     * TODO: [getTransactionInformation description]
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param string $key
     *
     * @return array|string|null
     */
    public function getTransactionInformation($transaction, $key = null) {
        $additionalInformation = $transaction->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        if (is_array($additionalInformation)) {
            if (null !== $key) {
                if (array_key_exists($key, $additionalInformation)) {
                    return $additionalInformation[$key];
                }
            } else {
                return $additionalInformation;
            }
        }
        return null;
    }

    public function getTransactionReasonCode($transaction) {
        $statusArray = $transaction->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        if (is_array($statusArray) && array_key_exists('ReasonCode', $statusArray)) {
            return $statusArray['ReasonCode'];
        }
        return null;
    }

    public function explodeCustomerName($customerName, $emptyValuePlaceholder = 'n/a') {
        $explodedName = explode(' ', trim($customerName));
        $result = array();
        if (count($explodedName) > 1) {
            $result['firstname'] = reset($explodedName);
            $result['lastname'] = trim(str_replace($result['firstname'], "", $customerName));
        } else {
            $result['firstname'] = $emptyValuePlaceholder ? Mage::helper('amazonpayments')->__($emptyValuePlaceholder) : null;
            $result['lastname'] = reset($explodedName);
        }
        return new Varien_Object($result);
    }

    public function getHeadCss() {
        if ($this->_getConfig()->isActive()) {
            return 'creativestyle/css/amazonpayments.css';
        }
    }

    public function getWidgetsCss() {
        if ($this->_getConfig()->isActive()) {
            if ($this->_getConfig()->isResponsive()) {
                return 'creativestyle/css/amazonpayments-responsive-widgets.css';
            } else {
                return 'creativestyle/css/amazonpayments-widgets.css';
            }
        }
    }

    public function getHeadJs() {
        if ($this->_getConfig()->isActive()) {
            if ($this->_getConfig()->isSandbox()) {
                return 'creativestyle/apa_checkout.js';
            }
            return 'creativestyle/apa_checkout.min.js';
        }
    }

    public function getHeadTooltipJs() {
        if ($this->_getConfig()->isActive()) {
            return 'prototype/tooltip.js';
        }
    }

    public function getPayWithAmazonButton($buttonType = null, $buttonSize = null, $buttonColor = null, $idSuffix = null) {
        return Mage::getSingleton('core/layout')->createBlock('amazonpayments/pay_button')
            ->setData('button_type', $buttonType)
            ->setData('button_size', $buttonSize)
            ->setData('button_color', $buttonColor)
            ->setData('id_suffix', $idSuffix)
            ->toHtml();
    }

    public function getLoginWithAmazonButton($buttonType = null, $buttonSize = null, $buttonColor = null, $idSuffix = null) {
        return Mage::getSingleton('core/layout')->createBlock('amazonpayments/login_button')
            ->setData('button_type', $buttonType)
            ->setData('button_size', $buttonSize)
            ->setData('button_color', $buttonColor)
            ->setData('id_suffix', $idSuffix)
            ->toHtml();
    }

}
