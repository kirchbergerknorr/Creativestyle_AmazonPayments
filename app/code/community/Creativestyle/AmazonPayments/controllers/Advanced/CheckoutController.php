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
class Creativestyle_AmazonPayments_Advanced_CheckoutController extends Mage_Core_Controller_Front_Action {

    protected $_orderReferenceId = null;

    protected $_accessToken = null;

    protected function _getCheckout() {
        return Mage::getSingleton('amazonpayments/checkout');
    }

    protected function _getCheckoutSession() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getOrderReferenceId() {
        return $this->_orderReferenceId;
    }

    protected function _getAccessToken() {
        return $this->_accessToken;
    }

    protected function _getApi() {
        return Mage::getModel('amazonpayments/api_advanced');
    }

    protected function _getQuote() {
        return $this->_getCheckout()->getQuote();
    }

    protected function _getShippingMethodsHtml() {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_advanced_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _getReviewHtml() {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_advanced_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _isSubmitAllowed() {
        if (!$this->_getQuote()->isVirtual()) {
            $address = $this->_getQuote()->getShippingAddress();
            $method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($method);
            if (!$this->_getQuote()->isVirtual() && (!$method || !$rate)) {
                return false;
            }
        }

        // TODO add checking if customer selected payment method in the Amazon Wallet widget

        return true;
    }

    protected function _saveOrderReferenceInSessionData() {
        if (null !== $this->_getOrderReferenceId() && !$this->_getCheckoutSession()->getOrderReferenceId()) {
            $this->_getApi()->setOrderReferenceDetails($this->_getOrderReferenceId(), $this->_getQuote()->getBaseGrandTotal(), $this->_getQuote()->getBaseCurrencyCode());
            $this->_getCheckoutSession()->setOrderReferenceId($this->_getOrderReferenceId());
            $this->_getCheckoutSession()->setCartWasUpdated(false);
        }
    }

    /**
     * Send Ajax redirect response
     *
     * @return Creativestyle_AmazonPayments_Advanced_CheckoutController
     */
    protected function _ajaxRedirectResponse() {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax() {
        if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        if ($this->_getCheckoutSession()->getCartWasUpdated(true)) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        if (null === $this->_getOrderReferenceId()) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        return false;
    }

    public function preDispatch() {
        parent::preDispatch();
        if (null === $this->_orderReferenceId) {
            $this->_orderReferenceId = $this->getRequest()->getParam('orderReferenceId', null);
        }
        // check session data for OrderReference's ID if not provided as parameter
        if (null === $this->_orderReferenceId) {
            $this->_orderReferenceId = $this->_getCheckoutSession()->getOrderReferenceId();
        }
        if (null === $this->_accessToken) {
            $this->_accessToken = $this->getRequest()->getParam('accessToken', null);
        }
    }

    public function indexAction() {
        try {
            if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
                $this->_redirect('checkout/cart');
                return;
            }

            if (!$this->_getQuote()->validateMinimumAmount()) {
                $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
                $this->_getCheckoutSession()->addError($error);
                $this->_redirect('checkout/cart');
                return;
            }

            if (null === $this->_getOrderReferenceId() && null === $this->_getAccessToken()) {
                $this->_redirect('checkout/cart');
                return;
            }

            $this->_getCheckoutSession()->setCartWasUpdated(false);

            $this->_saveOrderReferenceInSessionData();

            $this->loadLayout();
            $this->getLayout()->getBlock('head')->setTitle($this->__('Pay with Amazon'));
            $this->renderLayout();
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            $this->_getCheckoutSession()->addError($this->__('There was an error processing your order. Please contact us or try again later.'));
            $this->_redirect('checkout/cart');
            return;
        }
    }

    public function saveShippingAction() {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $this->_saveOrderReferenceInSessionData();

                $orderReference = $this->_getApi()->getOrderReferenceDetails($this->_getOrderReferenceId());
                // save billing data in the checkout model
                $result = $this->_getCheckout()->saveShipping(array(
                    'city' => $orderReference->getDestination()->getPhysicalDestination()->getCity(),
                    'postcode' => $orderReference->getDestination()->getPhysicalDestination()->getPostalCode(),
                    'country_id' => $orderReference->getDestination()->getPhysicalDestination()->getCountryCode(),
                    'use_for_shipping' => true
                ), false);
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage()
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'shipping-method' => $this->_getShippingMethodsHtml()
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            };
        } else {
            $this->_forward('noRoute');
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveShippingMethodAction() {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $this->_saveOrderReferenceInSessionData();

                $data = $this->getRequest()->getPost('shipping_method', '');
                $result = $this->_getCheckout()->saveShippingMethod($data);

                if (!empty($data) && !isset($result['error'])) {
                    Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                        array(
                            'request' => $this->getRequest(),
                            'quote' => $this->_getQuote()
                        )
                    );
                }
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage()
                );
            }

            if (!isset($result['error'])) {
                $this->_getQuote()->collectTotals()->save();
                $result = array(
                    'render_widget' => array(
                        'review' => $this->_getReviewHtml()
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            }
        } else {
            $this->_forward('noRoute');
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveOrderAction() {
        $result = array();
        try {
            $requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
            if ($requiredAgreements) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                $diff = array_diff($requiredAgreements, $postedAgreements);
                if ($diff) {
                    $result['success'] = false;
                    $result['error'] = true;
                    $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }

            $giftMessages = $this->getRequest()->getPost('giftmessage');
            if (is_array($giftMessages)) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                    array(
                        'request' => $this->getRequest(),
                        'quote' => $this->_getQuote()
                    )
                );
            }

            $this->_getCheckout()->savePayment(null);

            $this->_getQuote()->getPayment()->setTransactionId($this->_getOrderReferenceId());
            $simulation = $this->getRequest()->getPost('simulation', array());
            if (!empty($simulation)) {
                $simulationData = array(
                    'object' => isset($simulation['object']) ? $simulation['object'] : null,
                    'state' => isset($simulation['state']) ? $simulation['state'] : null,
                    'reason_code' => isset($simulation['reason']) ? $simulation['reason'] : null
                );
                $simulationData['options'] = Creativestyle_AmazonPayments_Model_Simulator::getSimulationOptions($simulationData['object'], $simulationData['state'], $simulationData['reason_code']);
                $this->_getQuote()->getPayment()->setSimulationData($simulationData);
            }
            $this->_getCheckout()->saveOrder();
            $this->_getQuote()->save();
            $result['success'] = true;
            $result['error']   = false;
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->_getQuote(), $e->getMessage());
            $this->_getCheckoutSession()->addError($this->__('There was an error processing your order. Please contact us or try again later.'));
            $result = array();
            $result['success'] = false;
            $result['redirect'] = Mage::getUrl('checkout/cart');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

}
