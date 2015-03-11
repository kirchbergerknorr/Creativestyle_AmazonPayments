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
abstract class Creativestyle_AmazonPayments_Model_Payment_Advanced_Abstract extends Creativestyle_AmazonPayments_Model_Payment_Abstract {

    protected $_code                    = 'amazonpayments_advanced_abstract';
    protected $_infoBlockType           = 'amazonpayments/payment_info';

    /**
     * Pay with Amazon method features
     * @var bool
     */
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = false;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = false;

    protected function _getApi() {
        return Mage::getSingleton('amazonpayments/api_advanced');
    }

    protected function _getManager() {
        return Mage::getSingleton('amazonpayments/manager');
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _increaseSequenceNumber(Varien_Object $payment) {
        $sequenceNumber = $payment->getAdditionalInformation('amazon_sequence_number');
        $sequenceNumber = is_null($sequenceNumber) ? 1 : ++$sequenceNumber;
        $payment->setAdditionalInformation('amazon_sequence_number', $sequenceNumber);
        return $sequenceNumber;
    }

    protected function _authorize(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();

        $authorizationDetails = $this->_getApi()->setStore($order->getStoreId())->authorize(
            $order->getExtOrderId(),
            $order->getExtOrderId() . '-' . $this->_increaseSequenceNumber($payment),
            $amount,
            $order->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'Authorization')
        );

        $payment->setTransactionId($authorizationDetails->getAmazonAuthorizationId());
        $payment->setParentTransactionId($order->getExtOrderId());
        $payment->setIsTransactionClosed(false);

        $message = Mage::helper('amazonpayments')->__('An authorize request for %s has been submitted to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false, $message);
        $transactionStatus = $this->_getManager()->importAuthorizationDetails(
            $authorizationDetails,
            $payment,
            $transaction
        );

        if (null !== $transactionStatus) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
            $transaction->save();
        }

        return $this;
    }

    protected function _capture(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();

        $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $authTransactionId = $authTransaction->getTxnId();

        $captureDetails = $this->_getApi()->setStore($order->getStoreId())->capture(
            $authTransactionId,
            $order->getExtOrderId() . '-' . $this->_increaseSequenceNumber($payment),
            $amount,
            $order->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'Capture')
        );

        $payment->setTransactionId($captureDetails->getAmazonCaptureId());
        $payment->setParentTransactionId($authTransactionId);
        $payment->setIsTransactionClosed(false);

        $message = Mage::helper('amazonpayments')->__('A capture request for %s has been submitted to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false, $message);
        $transactionStatus = $this->_getManager()->importCaptureDetails(
            $captureDetails,
            $payment,
            $transaction
        );

        if (null !== $transactionStatus) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
            $transaction->save();
        }

        return $this;
    }

    protected function _refund(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();

        $captureTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        $captureTransactionId = $captureTransaction->getTxnId();

        $refundDetails = $this->_getApi()->setStore($order->getStoreId())->refund(
            $captureTransactionId,
            $order->getExtOrderId() . '-' . $this->_increaseSequenceNumber($payment),
            $amount,
            $order->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'Refund')
        );

        $payment->setTransactionId($refundDetails->getAmazonRefundId());
        $payment->setParentTransactionId($captureTransactionId);
        $payment->setIsTransactionClosed(false);

        $message = Mage::helper('amazonpayments')->__('A refund request for %s has been submitted to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, null, false, $message);
        $transactionStatus = $this->_getManager()->importRefundDetails(
            $refundDetails,
            $payment,
            $transaction
        );

        if (null !== $transactionStatus) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
            $transaction->save();
        }

        return $this;
    }

    protected function _void(Varien_Object $payment) {
        $order = $payment->getOrder();

        $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        $orderTransactionId = $orderTransaction->getTxnId();

        $cancelDetails = $this->_getApi()->setStore($order->getStoreId())->cancelOrderReference($orderTransactionId);
        $orderTransaction->close(true);

        return $this;
    }

    /**
     * Check whether Amazon Payments Advanced is enabled
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null) {
        $checkResult = new StdClass;
        $isActive = $this->_getConfig()->isActive();
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

    /**
     * Payment initialization routines
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject) {
        $payment = $this->getInfoInstance();
        if ($payment) {
            $order = $payment->getOrder();
            $this->setStore($order->getStoreId())->order($payment, $order->getBaseTotalDue());
            $stateObject = new Varien_Object(array(
                'state' => Mage_Sales_Model_Order::STATE_NEW,
                'status' => true,
                'is_notified' => Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE
            ));
        }
        return $this;
    }

    /**
     * Check authorise availability
     *
     * @return bool
     */
    public function canAuthorize() {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            $payment = $this->getInfoInstance();
            if ($payment) {
                $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
                if (!$orderTransaction || $orderTransaction->getIsClosed()) {
                    return false;
                }
            }
        }
        return parent::canAuthorize();
    }

    /**
     * Check fetch transaction info availability
     *
     * @return bool
     */
    public function canFetchTransactionInfo() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed()) {
            return false;
        }
        return parent::canFetchTransactionInfo();
    }

    /**
     * Check invoice creating availability
     *
     * @return bool
     */
    public function canInvoice() {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            $payment = $this->getInfoInstance();
            if ($payment) {
                $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                if (!$authTransaction || $authTransaction->getIsClosed()) {
                    return false;
                }
            }
        }
        return parent::canInvoice();
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture() {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            $payment = $this->getInfoInstance();
            if ($payment) {
                $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                if (!$authTransaction || $authTransaction->getIsClosed()) {
                    return false;
                }
            }
        }
        return parent::canCapture();
    }

    /**
     * Transfer an order to Amazon Payments gateway
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Creativestyle_AmazonPayments_Model_Payment_Advanced_Abstract
     */
    public function order(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();

        $this->_getApi()->setOrderReferenceDetails($order->getExtOrderId(), $amount, $order->getBaseCurrencyCode(), $order->getIncrementId());
        $this->_getApi()->confirmOrderReference($order->getExtOrderId());

        $payment->setIsTransactionClosed(false);
        $payment->setSkipOrderProcessing(true);

        $message = Mage::helper('amazonpayments')->__('An order of %s has been sent to Amazon Payments.', $order->getStore()->convertPrice($amount, true, false));
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER, null, false, $message);
        $transactionStatus = $this->_getManager()->importOrderReferenceDetails(
            $this->_getApi()->getOrderReferenceDetails($order->getExtOrderId()),
            $payment,
            $transaction
        );

        if (null !== $transactionStatus) {
            $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
            $transaction->save();
        }

        Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'OrderReference');

        if ($this->_getConfig()->authorizeImmediately()) {
            $auth = $this->_authorize($payment, $amount);
        }

        return $this;
    }

    /**
     * Authorize
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Creativestyle_AmazonPayments_Model_Payment_Advanced_Abstract
     */
    public function authorize(Varien_Object $payment, $amount) {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            if (!$this->canAuthorize()) {
                throw new Creativestyle_AmazonPayments_Exception('Authorize action is not available');
            }
            $this->_authorize($payment, $amount);
        }
        return $this;
    }

    /**
     * Capture
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Creativestyle_AmazonPayments_Model_Payment_Advanced_Abstract
     */
    public function capture(Varien_Object $payment, $amount) {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            if (!$this->canCapture()) {
                throw new Creativestyle_AmazonPayments_Exception('Capture action is not available');
            }
            $this->_capture($payment, $amount);
        }
        return $this;
    }

    public function refund(Varien_Object $payment, $amount) {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            if (!$this->canRefund()) {
                throw new Creativestyle_AmazonPayments_Exception('Refund action is not available');
            }
            $this->_refund($payment, $amount);
        }
        return $this;
    }

    public function cancel(Varien_Object $payment) {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            return $this->_void($payment);
        }
        return $this;
    }

    public function void(Varien_Object $payment) {
        if ($this->_getConfig()->isPaymentProcessingAllowed()) {
            return $this->_void($payment);
        }
        return $this;
    }

    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId) {
        $transaction = $payment->lookupTransaction($transactionId);
        if ($transaction) {
            if ($transaction->getIsClosed()) {
                throw new Creativestyle_AmazonPayments_Exception('Cannot fetch information for closed transaction.');
            }
            return $this->_getManager()->importTransactionDetails($payment, $transaction);
        }
        throw new Creativestyle_AmazonPayments_Exception('Transaction not found.');
    }

    public function closeOrderReference(Mage_Payment_Model_Info $payment) {
        $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        if ($orderTransaction && !$orderTransaction->getIsClosed()) {
            $this->_getApi()->setStore($payment->getOrder()->getStoreId())->closeOrderReference($orderTransaction->getTxnId());
            $orderTransaction->close(true);
        }
    }

}
