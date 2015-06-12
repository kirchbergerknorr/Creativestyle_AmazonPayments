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
 * @copyright  Copyright (c) 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Processor_Ipn {

    /**
     * Return Magento order processor instance
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _getOrderProcessor($order) {
        return Mage::getSingleton('amazonpayments/processor_order')->setOrder($order);
    }

    /**
     * TODO: [_getOrderReferenceId description]
     *
     * @param  string $transactionId
     * @return string
     */
    protected function _getOrderReferenceId($transactionId) {
        return substr($transactionId, 0, strrpos($transactionId, '-'));
    }

    /**
     * Return Amazon Payments processor instance
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Payment
     */
    protected function _getPaymentProcessor($payment) {
        return Mage::getSingleton('amazonpayments/processor_payment')->setPaymentObject($payment);
    }

    /**
     * TODO: [_lookupPayment description]
     *
     * @param  string $orderReferenceId
     * @return Mage_Sales_Model_Order_Payment|null
     */
    protected function _lookupPayment($orderReferenceId) {
        $order = Mage::getModel('sales/order')->loadByAttribute('ext_order_id', $orderReferenceId);
        if (is_object($order) && $order->getId()) return $order->getPayment();
        return null;
    }

    /**
     * Process a notification message requested via IPN
     *
     * @param OffAmazonPaymentNotifications_Notification $notification
     *
     * @throws Creativestyle_AmazonPayments_Exception
     */
    public function processNotification($notification) {
        if (null !== $notification) {
            $payment = null;
            $transaction = null;
            $transactionDetails = null;
            switch ($notification->getNotificationType()) {
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_ORDER_REFERENCE:
                    if ($notification->isSetOrderReference()) {
                        $payment = $this->_lookupPayment($notification->getOrderReference()->getAmazonOrderReferenceId());
                        $transaction = $payment->lookupTransaction($notification->getOrderReference()->getAmazonOrderReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
                        $transactionDetails = $notification->getOrderReference();
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('OrderReference field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_AUTHORIZATION:
                    if ($notification->isSetAuthorizationDetails()) {
                        $payment = $this->_lookupPayment($this->_getOrderReferenceId($notification->getAuthorizationDetails()->getAuthorizationReferenceId()));
                        $transaction = $payment->lookupTransaction($notification->getAuthorizationDetails()->getAmazonAuthorizationId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                        $transactionDetails = $notification->getAuthorizationDetails();
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('AuthorizationDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_CAPTURE:
                    if ($notification->isSetCaptureDetails()) {
                        $payment = $this->_lookupPayment($this->_getOrderReferenceId($notification->getCaptureDetails()->getCaptureReferenceId()));
                        $transaction = $payment->lookupTransaction($notification->getCaptureDetails()->getAmazonCaptureId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                        $transactionDetails = $notification->getCaptureDetails();
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('CaptureDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_REFUND:
                    if ($notification->isSetRefundDetails()) {
                        $payment = $this->_lookupPayment($this->_getOrderReferenceId($notification->getRefundDetails()->getRefundReferenceId()));
                        $transaction = $payment->lookupTransaction($notification->getRefundDetails()->getAmazonRefundId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
                        $transactionDetails = $notification->getRefundDetails();
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('RefundDetails field not found in submitted notification');
                    }
                    break;
                default:
                    throw new Creativestyle_AmazonPayments_Exception('Invalid notification type');
            }
            if ($payment && $transaction) {
                $transactionAdapter = $this->_getPaymentProcessor($payment)->importTransactionDetails($transaction);
                if ($transactionAdapter->getStatusChange()) {
                    $transactionAdapter->saveTransaction();
                    $this->_getOrderProcessor($payment->getOrder())
                        ->importTransactionDetails($transactionAdapter, new Varien_Object())
                        ->saveOrder();
                } else {
                    $relatedTransactionAdapter = $transactionAdapter->processRelatedObjects($payment->getOrder());
                    $this->_getOrderProcessor($payment->getOrder())->saveOrder();
                }
            } else {
                throw new Creativestyle_AmazonPayments_Exception('Payment transaction with such ID not found');
            }
        } else {
            throw new Creativestyle_AmazonPayments_Exception('No notification data provided');
        }
    }

}
