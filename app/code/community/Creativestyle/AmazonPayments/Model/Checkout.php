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
class Creativestyle_AmazonPayments_Model_Checkout extends Mage_Checkout_Model_Type_Onepage {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getPaymentMethod() {
        if ($this->_getConfig()->isSandbox()) {
            return array('method' => 'amazonpayments_advanced_sandbox');
        }
        return array('method' => 'amazonpayments_advanced');
    }

    public function savePayment($data) {
        $data = $this->_getPaymentMethod();
        if ($this->getQuote()->isVirtual()) {
            $this->getQuote()->getBillingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        } else {
            $this->getQuote()->getShippingAddress()->setPaymentMethod(isset($data['method']) ? $data['method'] : null);
        }

        // shipping totals may be affected by payment method
        if (!$this->getQuote()->isVirtual() && $this->getQuote()->getShippingAddress()) {
            $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        }

        $data['checks'] = Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_COUNTRY
            | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_USE_FOR_CURRENCY
            | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_ORDER_TOTAL_MIN_MAX;
//            | Creativestyle_AmazonPayments_Model_Payment_Abstract::CHECK_ZERO_TOTAL


        $payment = $this->getQuote()->getPayment();
        $payment->importData($data);

        $this->getQuote()->save();

        $this->getCheckout()
            ->setStepData('payment', 'complete', true)
            ->setStepData('review', 'allow', true);

        return array();
    }

    public function saveShipping($data, $customerAddressId) {
        if (empty($data)) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid data.'));
        }

        unset($data['address_id']);
        $address = $this->getQuote()->getBillingAddress();
        $address->setCustomerAddressId(null);

        $address->addData($data)->setSaveInAddressBook(0);
        $address->implodeStreetAddress();

        $this->getCheckout()->setStepData('billing', 'complete', true);

        if (!$this->getQuote()->isVirtual()) {
            $billing = clone $address;
            $billing->unsAddressId()->unsAddressType();
            $shipping = $this->getQuote()->getShippingAddress();
            $shippingMethod = $shipping->getShippingMethod();
            $shipping->addData($billing->getData())
                ->setSameAsBilling(1)
                ->setSaveInAddressBook(0)
                ->setShippingMethod($shippingMethod)
                ->setCollectShippingRates(true);
            $this->getCheckout()->setStepData('shipping', 'complete', true);
        }

        $this->getQuote()->collectTotals();
        $this->getQuote()->save();

        if (!$this->getQuote()->isVirtual() && $this->getCheckout()->getStepData('shipping', 'complete') == true) {
            // recollect shipping rates for shipping methods
            $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        }

        $this->getCheckout()->setStepData('shipping_method', 'allow', true);

        return array();
    }

    public function saveShippingMethod($shippingMethod) {
        if (empty($shippingMethod)) {
            $this->getQuote()->getShippingAddress()->unsetShippingMethod();
            return array();
        }
        $rate = $this->getQuote()->getShippingAddress()->getShippingRateByCode($shippingMethod);
        if (!$rate) {
            return array('error' => -1, 'message' => Mage::helper('checkout')->__('Invalid shipping method.'));
        }
        $this->getQuote()->getShippingAddress()
            ->setShippingMethod($shippingMethod)
            ->collectTotals()
            ->save();

        $this->getCheckout()
            ->setStepData('shipping_method', 'complete', true)
            ->setStepData('payment', 'allow', true);

        return array();
    }

    public function saveOrder() {
        $this->validate();
        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case self::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case self::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $service = Mage::getModel('amazonpayments/service_quote', $this->getQuote());
        $service->submitOrder();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        $this->_checkoutSession->setLastQuoteId($this->getQuote()->getId())
            ->setLastSuccessQuoteId($this->getQuote()->getId())
            ->clearHelperData();

        $order = $service->getOrder();
        if ($order) {
            Mage::dispatchEvent('checkout_type_onepage_save_order_after',
                array('order' => $order, 'quote' => $this->getQuote()));

            // add order information to the session
            $this->_checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setOrderReferenceId(null)
                ->setAmazonSequenceNumber(null);

        }

        Mage::dispatchEvent(
            'checkout_submit_all_after',
            array('order' => $order, 'quote' => $this->getQuote(), 'recurring_profiles' => null)
        );

        return $this;
    }

}
