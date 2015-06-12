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
 * @copyright  Copyright (c) 2014 - 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
abstract class Creativestyle_AmazonPayments_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {

    const ACTION_MANUAL                         = 'manual';
    const ACTION_AUTHORIZE                      = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE              = 'authorize_capture';
    const ACTION_ERP                            = 'erp';

    const CHECK_USE_FOR_COUNTRY                 = 1;
    const CHECK_USE_FOR_CURRENCY                = 2;
    const CHECK_USE_CHECKOUT                    = 4;
    const CHECK_USE_FOR_MULTISHIPPING           = 8;
    const CHECK_USE_INTERNAL                    = 16;
    const CHECK_ORDER_TOTAL_MIN_MAX             = 32;
    const CHECK_RECURRING_PROFILES              = 64;
    const CHECK_ZERO_TOTAL                      = 128;

    protected $_code                            = 'amazonpayments_abstract';
    protected $_infoBlockType                   = 'amazonpayments/payment_info';

    /**
     * Pay with Amazon method features
     *
     * @var bool
     */
    protected $_isGateway                   = false;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = false;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = true;

    /**
     * Return Amazon Payments config model instance
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Return Magento order processor instance
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _getOrderProcessor() {
        return Mage::getSingleton('amazonpayments/processor_order')->setOrder($this->getInfoInstance()->getOrder());
    }

    /**
     * Return Amazon Payments processor instance
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Payment
     */
    protected function _getPaymentProcessor() {
        return Mage::getSingleton('amazonpayments/processor_payment')->setPaymentObject($this->getInfoInstance());
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _initInfoInstance($payment) {
        if (!$this->hasInfoInstance()) {
            $this->setInfoInstance($payment);
        }
        if ($payment->getOrder() && null === $this->getStore()) {
            $this->setStore($payment->getOrder()->getStoreId());
        }
        return $this;
    }

    protected function _getPaymentSequenceId() {
        $sequenceNumber = $this->getInfoInstance()->getAdditionalInformation('amazon_sequence_number');
        $sequenceNumber = is_null($sequenceNumber) ? 1 : ++$sequenceNumber;
        $this->getInfoInstance()->setAdditionalInformation('amazon_sequence_number', $sequenceNumber);
        return sprintf('%s-%s', $this->getInfoInstance()->getOrder()->getExtOrderId(), $sequenceNumber);
    }

    /**
     * Check authorise availability
     *
     * @return bool
     */
    public function canAuthorize() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canAuthorize();
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCapture();
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     */
    public function canCapturePartial() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCapturePartial();
    }

    /**
     * Check whether capture can be performed once and no further capture possible
     *
     * @return bool
     */
    public function canCaptureOnce() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCaptureOnce();
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canRefund();
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canRefundPartialPerInvoice();
    }

    /**
     * Check void availability
     *
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canVoid($payment);
    }

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit() {
        return false;
    }

    /**
     * Check fetch transaction info availability
     *
     * @return bool
     */
    public function canFetchTransactionInfo() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canFetchTransactionInfo();
    }

    /**
     * Fetch transaction info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @param bool $shouldSave
     *
     * @return array|bool
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId, $shouldSave = true) {
        $this->_initInfoInstance($payment);
        if ($transaction = $payment->lookupTransaction($transactionId)) {
            $transactionAdapter = $this->_getPaymentProcessor()->importTransactionDetails($transaction);
            if ($transactionAdapter->getStatusChange()) {
                $this->_getOrderProcessor()->importTransactionDetails($transactionAdapter, new Varien_Object());
                if ($shouldSave) $this->_getOrderProcessor()->saveOrder();
            } else {
                $transactionAdapter->processRelatedObjects($this->getInfoInstance()->getOrder());
                if ($shouldSave) $this->_getOrderProcessor()->saveOrder();
            }
            return $transactionAdapter->getStatusChange();
        }
        throw new Creativestyle_AmazonPayments_Exception(sprintf('Transaction %s not found', $transactionId));
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode) {
        return true;
    }

    /**
     * Payment order
     *
     * @param float $amount
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    protected function _order($amount, &$transaction = null) {
        if (!$this->canOrder()) {
            throw new Creativestyle_AmazonPayments_Exception('Order action is not available');
        }
        $this->_getPaymentProcessor()->order($amount, $this->getInfoInstance()->getTransactionId());
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        return $this->_getPaymentProcessor()->importTransactionDetails($transaction);
    }

    /**
     * Payment authorize
     *
     * @param float $amount
     * @param string $parentTransactionId
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param bool $captureNow
     *
     * @return array|null
     */
    protected function _authorize($amount, $parentTransactionId, &$transaction = null, $captureNow = false, $synchronous = false) {
        if (!$this->canAuthorize()) {
            throw new Creativestyle_AmazonPayments_Exception('Authorize action is not available');
        }
        $authorizationDetails = $this->_getPaymentProcessor()->authorize(
            $amount,
            $this->_getPaymentSequenceId(),
            $parentTransactionId,
            $captureNow,
            $synchronous
        );
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->setTransactionId($authorizationDetails->getAmazonAuthorizationId())
            ->setParentTransactionId($parentTransactionId)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        return $this->_getPaymentProcessor()->importTransactionDetails($transaction);
    }

    /**
     * Payment capture
     *
     * @param float $amount
     * @param string $parentTransactionId
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    protected function _capture($amount, $parentTransactionId, &$transaction = null) {
        if (!$this->canCapture()) {
            throw new Creativestyle_AmazonPayments_Exception('Capture action is not available');
        }
        $captureDetails = $this->_getPaymentProcessor()->capture(
            $amount,
            $this->_getPaymentSequenceId(),
            $parentTransactionId
        );
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->setTransactionId($captureDetails->getAmazonCaptureId())
            ->setParentTransactionId($parentTransactionId)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        return $this->_getPaymentProcessor()->importTransactionDetails($transaction, $captureDetails);
    }

    /**
     * Payment refund
     *
     * @param float $amount
     * @param string $parentTransactionId
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    protected function _refund($amount, $parentTransactionId, &$transaction = null) {
        if (!$this->canRefund()) {
            throw new Creativestyle_AmazonPayments_Exception('Capture action is not available');
        }
        $refundDetails = $this->_getPaymentProcessor()->refund(
            $amount,
            $this->_getPaymentSequenceId(),
            $parentTransactionId
        );
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->setTransactionId($refundDetails->getAmazonRefundId())
            ->setParentTransactionId($parentTransactionId)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
        return $this->_getPaymentProcessor()->importTransactionDetails($transaction, $refundDetails);
    }

    /**
     * Public wrapper for payment order
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function order(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);
        $orderTransaction = null;
        $orderReferenceAdapter = $this->_order($amount, $orderTransaction);
        $orderReferenceAdapter->validateTransactionStatus();
        $this->_getOrderProcessor()->importTransactionDetails($orderReferenceAdapter, $stateObject);
        return $this;
    }

    /**
     * Payment authorization public method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function authorize(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);
        if ($orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER)) {
            $authorizationTransaction = null;
            $authorizationAdapter = $this->_authorize(
                $amount,
                $orderTransaction->getTxnId(),
                $authorizationTransaction,
                $this->_getConfig()->getPaymentAction($payment->getOrder()->getStoreId()) == self::ACTION_AUTHORIZE_CAPTURE,
                $this->_getConfig()->isAuthorizationSynchronous($payment->getOrder()->getStoreId())
            );
            $authorizationAdapter->validateTransactionStatus();
            $this->_getOrderProcessor()->importTransactionDetails($authorizationAdapter, $stateObject);
        }
        return $this;
    }

    /**
     * Payment capture public method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);
        if ($authorizationTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)) {
            $captureTransaction = null;
            $captureAdapter = $this->_capture(
                $amount,
                $authorizationTransaction->getTxnId(),
                $captureTransaction
            );
            $captureAdapter->validateTransactionStatus();
            $this->_getOrderProcessor()->importTransactionDetails($captureAdapter, $stateObject)->saveOrder();
            // avoid transaction duplicates
            $payment->setSkipTransactionCreation(true);
        }
        return $this;
    }

    /**
     * @todo
     * Set capture transaction ID to invoice for informational purposes
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment) {
        $invoice->setTransactionId($payment->getLastTransId());
        return $this;
    }

    /**
     * @todo
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);
        if ($captureTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)) {
            $refundTransaction = null;
            $refundAdapter = $this->_refund(
                $amount,
                $captureTransaction->getTxnId(),
                $refundTransaction
            );
            $refundAdapter->validateTransactionStatus();
            $this->_getOrderProcessor()->importTransactionDetails($refundAdapter, $stateObject)->saveOrder();
            // avoid transaction duplicates
            $payment->setSkipTransactionCreation(true);
        }
        return $this;
    }

    /**
     * @todo
     * Set transaction ID into creditmemo for informational purposes
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment) {
        $creditmemo->setTransactionId($payment->getLastTransId());
        return $this;
    }

    /**
     * @todo
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        return $this;
    }

    /**
     * @todo
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        if (!$this->canVoid($payment)) {
            throw new Creativestyle_AmazonPayments_Exception('Void action is not available');
        }
        return $this;
    }

    /**
     * Modified payment configuration retriever
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null) {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        switch ($field) {
            case 'payment_action':
                return $this->_getConfig()->getPaymentAction($storeId);
            default:
                return parent::getConfigData($field, $storeId);
        }
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function initialize($paymentAction, $stateObject) {
        $payment = $this->getInfoInstance();
        $this->setStore($payment->getOrder()->getStoreId());
        switch ($paymentAction) {
            case self::ACTION_MANUAL:
                $orderTransaction = null;
                $orderReferenceAdapter = $this->_order($this->getInfoInstance()->getOrder()->getBaseTotalDue(), $orderTransaction);
                $orderReferenceAdapter->validateTransactionStatus();
                $this->_getOrderProcessor()->importTransactionDetails($orderReferenceAdapter, $stateObject);
                break;
            case self::ACTION_AUTHORIZE:
            case self::ACTION_AUTHORIZE_CAPTURE:
                // OrderReference first
                $orderTransaction = null;
                $orderReferenceAdapter = $this->_order($this->getInfoInstance()->getOrder()->getBaseTotalDue(), $orderTransaction);
                $orderReferenceAdapter->validateTransactionStatus();
                $this->_getOrderProcessor()->importTransactionDetails($orderReferenceAdapter, $stateObject);
                // Authorization next
                $authorizationTransaction = null;
                $authorizationAdapter = $this->_authorize(
                    $this->getInfoInstance()->getOrder()->getBaseTotalDue(),
                    $orderTransaction->getTxnId(),
                    $authorizationTransaction,
                    $paymentAction == self::ACTION_AUTHORIZE_CAPTURE,
                    $this->_getConfig()->isAuthorizationSynchronous($payment->getOrder()->getStoreId())
                );
                $authorizationAdapter->validateTransactionStatus();
                $this->_getOrderProcessor()->importTransactionDetails($authorizationAdapter, $stateObject);
                break;
        }
        return $this;
    }

    /**
     * Check whether Pay with Amazon is available
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null) {
        $checkResult = new StdClass;
        $isActive = $this->_getConfig()->isActive($quote ? $quote->getStoreId() : null) & Creativestyle_AmazonPayments_Model_Config::PAY_WITH_AMAZON_ACTIVE;
        if ($quote && !$quote->validateMinimumAmount()) {
            $isActive = false;
        }
        $checkResult->isAvailable = $isActive;
        $checkResult->isDeniedInConfig = !$isActive;
        Mage::dispatchEvent('payment_method_is_active', array(
            'result' => $checkResult,
            'method_instance' => $this,
            'quote' => $quote,
        ));
        return $checkResult->isAvailable;
    }

}
