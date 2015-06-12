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
class Creativestyle_AmazonPayments_Model_Processor_Payment {

    /**
     * TODO: Payment info instance
     *
     * @var null
     */
    protected $_payment = null;

    /**
     * TODO: [$_store description]
     *
     * @var null
     */
    protected $_store = null;

    /**
     * TODO: [setPaymentObject description]
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Payment
     */
    public function setPaymentObject($payment) {
        $this->_payment = $payment;
        $this->_store = $payment->getOrder()->getStoreId();
        return $this;
    }

    /**
     * TODO: [getPayment description]
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    public function getPayment() {
        if (null === $this->_payment) {
            throw new Creativestyle_AmazonPayments_Exception('Payment object is not set');
        }
        return $this->_payment;
    }

    /**
     * TODO: [_getApi description]
     *
     * @return [type] [description]
     */
    protected function _getApi() {
        return Mage::getSingleton('amazonpayments/api_advanced')->setStore($this->_store);
    }

    /**
     * Import transaction details to the Magento order and its related objects
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return Varien_Object
     */
    protected function _importTransactionDetails($transaction, $transactionDetails = null) {
        $transactionAdapter = Mage::getModel('amazonpayments/processor_transactionAdapter');
        $transactionAdapter->setStore($this->_store)
            ->setTransaction($transaction)
            ->setTransactionDetails($transactionDetails);
        return $transactionAdapter;
    }

    /**
     * Transfer order to Amazon Payments gateway
     *
     * @param  float $amount
     * @param  string $transactionSequenceId
     */
    public function order($amount, $transactionSequenceId) {
        if (!$this->getPayment()->getSkipOrderReferenceProcessing()) {
            $this->_getApi()->setOrderReferenceDetails(
                $transactionSequenceId,
                $amount,
                $this->getPayment()->getOrder()->getBaseCurrencyCode(),
                $this->getPayment()->getOrder()->getIncrementId()
            );
        }
        $this->_getApi()->confirmOrderReference($transactionSequenceId);
        Creativestyle_AmazonPayments_Model_Simulator::simulate($this->getPayment(), 'OrderReference');
    }

    /**
     * Authorize order amount on Amazon Payments gateway
     *
     * @param  float $amount
     * @param  string $transactionSequenceId
     * @param  string $parentTransactionId
     * @param  bool $captureNow
     * @param  bool $synchronous
     *
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function authorize($amount, $transactionSequenceId, $parentTransactionId, $captureNow = false, $synchronous = false) {
        return $this->_getApi()->authorize(
            $parentTransactionId,
            $transactionSequenceId,
            $amount,
            $this->getPayment()->getOrder()->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($this->getPayment(), 'Authorization'),
            $captureNow,
            $synchronous ? 0 : null
        );
    }

    /**
     * Capture order amount on Amazon Payments gateway
     *
     * @param  float $amount
     * @param  string $transactionSequenceId
     * @param  string $parentTransactionId
     *
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function capture($amount, $transactionSequenceId, $parentTransactionId) {
        return $this->_getApi()->capture(
            $parentTransactionId,
            $transactionSequenceId,
            $amount,
            $this->getPayment()->getOrder()->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($this->getPayment(), 'Capture')
        );
    }

    /**
     * Refund amount on Amazon Payments gateway
     *
     * @param  float $amount
     * @param  string $transactionSequenceId
     * @param  string $parentTransactionId
     *
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function refund($amount, $transactionSequenceId, $parentTransactionId) {
        return $this->_getApi()->refund(
            $parentTransactionId,
            $transactionSequenceId,
            $amount,
            $this->getPayment()->getOrder()->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($this->getPayment(), 'Refund')
        );
    }

    /**
     * Public wrapper for _importTransactionDetails() method
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return Varien_Object
     */
    public function importTransactionDetails($transaction, $transactionDetails = null) {
        return $this->_importTransactionDetails($transaction, $transactionDetails);
    }

}
