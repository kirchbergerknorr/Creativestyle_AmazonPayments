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
class Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter {

    const TRANSACTION_STATE_KEY                 = 'State';
    const TRANSACTION_REASON_CODE_KEY           = 'ReasonCode';
    const TRANSACTION_REASON_DESCRIPTION_KEY    = 'ReasonDescription';

    const TRANSACTION_STATE_DRAFT               = 'Draft';
    const TRANSACTION_STATE_PENDING             = 'Pending';
    const TRANSACTION_STATE_OPEN                = 'Open';
    const TRANSACTION_STATE_SUSPENDED           = 'Suspended';
    const TRANSACTION_STATE_DECLINED            = 'Declined';
    const TRANSACTION_STATE_COMPLETED           = 'Completed';
    const TRANSACTION_STATE_CANCELED            = 'Canceled';
    const TRANSACTION_STATE_CLOSED              = 'Closed';

    const TRANSACTION_REASON_INVALID_PAYMENT    = 'InvalidPaymentMethod';
    const TRANSACTION_REASON_TIMEOUT            = 'TransactionTimedOut';
    const TRANSACTION_REASON_AMAZON_REJECTED    = 'AmazonRejected';

    /**
     * TODO: [$_store description]
     *
     * @var null
     */
    protected $_store = null;

    /**
     * Magento payment transaction instance
     *
     * @var Mage_Sales_Model_Order_Payment_Transaction|null
     */
    protected $_transaction = null;

    /**
     * Transaction details object retrieved from Amazon Payments API
     *
     * @var OffAmazonPaymentsService_Model|OffAmazonPayments_Model|null
     */
    protected $_transactionDetails = null;

    /**
     * Transaction amount extracted from transaction details
     *
     * @var float|null
     */
    protected $_transactionAmount = null;

    /**
     * An array reflecting transaction status change in the recently
     * got Amazon Payments API call response in comparison to
     * corresponding Magento transaction saved in database
     *
     * @var array|false|null
     */
    protected $_statusChange = null;

    /**
     * TODO: [_getApi description]
     *
     * @return Creativestyle_AmazonPayments_Model_Api_Advanced
     */
    protected function _getApi() {
        return Mage::getSingleton('amazonpayments/api_advanced')->setStore($this->_store);
    }

    /**
     * Extract transaction status from the transaction details
     *
     * Extract and return transaction status array (state, reason code)
     * from the transaction details.
     *
     * @param string $key
     *
     * @return array|null
     */
    protected function _extractTransactionStatus($key = null) {
        if (null !== $this->_transaction && $this->getTransactionDetails()) {
            if (call_user_func(array($this->getTransactionDetails(), 'isSet' . $this->_getAmazonTransactionType() . 'Status'))) {
                $transactionStatus = call_user_func(array($this->getTransactionDetails(), 'get' . $this->_getAmazonTransactionType() . 'Status'));
                $status = array(self::TRANSACTION_STATE_KEY => $transactionStatus->getState());
                if ($transactionStatus->isSetReasonCode()) {
                    $status[self::TRANSACTION_REASON_CODE_KEY] = $transactionStatus->getReasonCode();
                }
                if ($transactionStatus->isSetReasonDescription()) {
                    $status[self::TRANSACTION_REASON_DESCRIPTION_KEY] = $transactionStatus->getReasonDescription();
                }
                if (null !== $key) {
                    if (array_key_exists($key, $status)) {
                        return $status[$key];
                    }
                    return null;
                }
                return $status;
            }
        }
        return null;
    }

    /**
     * Check if transaction status has changed
     *
     * Extract transaction status from the transaction details. If the
     * status in assigned transaction details is the same as in
     * corresponding transaction saved in Magento then return false to
     * distinguish in the further processes that there's no need to
     * update payment or order data.
     *
     * @return array|false
     */
    protected function _checkStatusChange() {
        $transactionStatus = $this->_extractTransactionStatus();
        if (null !== $transactionStatus && Mage::helper('amazonpayments')->getTransactionInformation($this->_transaction, self::TRANSACTION_STATE_KEY) != $transactionStatus[self::TRANSACTION_STATE_KEY]) {
            return $transactionStatus;
        }
        return false;
    }

    /**
     * TODO: [_extractIdList description]
     *
     * @return array|null
     */
    protected function _extractIdList() {
        $transactionDetails = $this->getTransactionDetails();
        if (is_callable(array($transactionDetails, 'isSetIdList'))) {
            if ($transactionDetails->isSetIdList()) {
                $idList = $transactionDetails->getIdList();
                if (is_callable(array($idList, 'isSetmember'))) {
                    if ($idList->isSetmember()) {
                        return $idList->getmember();
                    }
                } else if (is_callable(array($idList, 'isSetId'))) {
                    if ($idList->isSetId()) {
                        return $idList->getId();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Retrieve transaction details from Amazon Payments API
     *
     * Retrieves details for provided Magento transaction object using
     * Amazon Payments API client. Before making a call, identifies the
     * type of provided transaction type by using appropriate function.
     *
     * @return OffAmazonPayments_Model|null
     */
    protected function _fetchTransactionDetails() {
        if (null !== $this->_transaction) {
            return call_user_func(array($this->_getApi(), 'get' . $this->_getAmazonTransactionType() . 'Details'), $this->getTransactionId());
        }
        return null;
    }

    /**
     * Returns Amazon Payments-specific name for transaction type
     *
     * Checks the type of provided payment transaction object and
     * returns its corresponding Amazon transaction name. Returns
     * null if type of provided transaction object is neither
     * recognized nor has an Amazon Payments equivalent.
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return string|null
     */
    protected function _getAmazonTransactionType() {
        switch ($this->getTransactionType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                return 'OrderReference';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                return 'Authorization';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                return 'Capture';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                return 'Refund';
        }
        return null;
    }

    /**
     * Update status of Magento payment transaction object
     *
     * @param array $transactionStatus
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter
     */
    protected function _updateTransactionStatus($transactionStatus) {
        $this->_transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
        if (is_array($transactionStatus) && array_key_exists(self::TRANSACTION_STATE_KEY, $transactionStatus)) {
            switch ($transactionStatus[self::TRANSACTION_STATE_KEY]) {
                case self::TRANSACTION_STATE_DECLINED:
                case self::TRANSACTION_STATE_CANCELED:
                case self::TRANSACTION_STATE_CLOSED:
                case self::TRANSACTION_STATE_COMPLETED:
                    $this->_transaction->setIsClosed(true);
                    break;
                default:
                    break;
            }
        }
        return $this;
    }

    /**
     * TODO: [setStore description]
     *
     * @param [type] $store
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter
     */
    public function setStore($store) {
        $this->_store = $store;
        return $this;
    }

    /**
     * TODO: [setTransaction description]
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction [description]
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter
     */
    public function setTransaction($transaction) {
        $this->_transaction = $transaction;
        return $this;
    }

    /**
     * TODO: [setTransactionDetails description]
     *
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter
     */
    public function setTransactionDetails($transactionDetails) {
        $this->_transactionDetails = $transactionDetails;
        return $this;
    }

    /**
     * TODO: [getTransaction description]
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function getTransaction() {
        return $this->_transaction;
    }

    /**
     * TODO: [getTransactionType description]
     *
     * @return string|null
     */
    public function getTransactionType() {
        if (null !== $this->_transaction) {
            return $this->_transaction->getTxnType();
        }
        return null;
    }

    /**
     * TODO: [getTransactionId description]
     *
     * @return string
     */
    public function getTransactionId() {
        if (null !== $this->_transaction) {
            return $this->_transaction->getTxnId();
        }
        return null;
    }

    /**
     * TODO: [getTransactionDetails description]
     *
     * @return OffAmazonPaymentsService_Model|OffAmazonPayments_Model|null
     */
    public function getTransactionDetails() {
        if (null === $this->_transactionDetails) {
            $this->_transactionDetails = $this->_fetchTransactionDetails();
        }
        return $this->_transactionDetails;
    }

    /**
     * TODO: [getTransactionAmount description]
     *
     * @return float
     */
    public function getTransactionAmount() {
        if (null === $this->_transactionAmount) {
            $transactionAmountObject = call_user_func(array($this->getTransactionDetails(), $this->getTransactionType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER ? 'getOrderTotal' : 'get' . $this->_getAmazonTransactionType() . 'Amount'));
            $this->_transactionAmount = $transactionAmountObject->getAmount();
        }
        return $this->_transactionAmount;
    }

    /**
     * TODO: [getStatusChange description]
     *
     * @param string $key
     *
     * @return array|string|false|null
     */
    public function getStatusChange($key = null) {
        if (null === $this->_statusChange) {
            $this->_statusChange = $this->_checkStatusChange();
            if ($this->_statusChange) {
                $this->_updateTransactionStatus($this->_statusChange);
            }
        }
        if (null !== $key) {
            if (is_array($this->_statusChange) && array_key_exists($key, $this->_statusChange)) {
                return $this->_statusChange[$key];
            }
            return null;
        }
        return $this->_statusChange;
    }

    /**
     * TODO: [processRelatedObjects description]
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter|null
     */
    public function processRelatedObjects($order) {
        switch ($this->getTransactionType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                if ($this->_extractTransactionStatus(self::TRANSACTION_STATE_KEY) == self::TRANSACTION_STATE_OPEN) {
                    $authTransaction = $order->getPayment()->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    if ($authTransaction && $authTransaction->getIsClosed() && ($order->getBaseTotalDue() > 0)) {
                        $order->getPayment()->getMethodInstance()->setStore($order->getStoreId())->authorize($order->getPayment(), $order->getBaseTotalDue());
                    }
                }
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                $childrenIds = $this->_extractIdList();
                if (is_array($childrenIds)) {
                    foreach ($childrenIds as $childTransactionId) {
                        if (!$order->getPayment()->lookupTransaction($childTransactionId)) {
                            $childTransaction = $order->getPayment()->setIsTransactionClosed(false)
                                ->setTransactionId($childTransactionId)
                                ->setParentTransactionId($this->getTransactionId())
                                ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                            $captureAdapter = Mage::getSingleton('amazonpayments/processor_payment')
                                ->setPaymentObject($order->getPayment())
                                ->importTransactionDetails($childTransaction);
                            $captureAdapter->validateTransactionStatus();
                            $invoice = $order->prepareInvoice()
                                ->setTransactionId($childTransactionId)
                                ->register()
                                ->pay();
                            $order->addRelatedObject($invoice);
                            return $captureAdapter;
                        }
                    }
                }
                break;
        }
        return null;
    }

    /**
     * TODO: [saveTransaction description]
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter
     */
    public function saveTransaction() {
        $this->_transaction->save();
        return $this;
    }

    /**
     * TODO: [validateTransactionStatus description]
     *
     * @return bool
     *
     * @throws Creativestyle_AmazonPayments_Exception_InvalidStatus_Recoverable
     * @throws Creativestyle_AmazonPayments_Exception_InvalidStatus
     * @throws Creativestyle_AmazonPayments_Exception
     */
    public function validateTransactionStatus() {
        $transactionStatus = $this->getStatusChange();
        if (is_array($transactionStatus)) {
            $state = array_key_exists(self::TRANSACTION_STATE_KEY, $transactionStatus) ? $transactionStatus[self::TRANSACTION_STATE_KEY] : null;
            $reason = array_key_exists(self::TRANSACTION_REASON_CODE_KEY, $transactionStatus) ? $transactionStatus[self::TRANSACTION_REASON_CODE_KEY] : null;
            switch ($this->getTransactionType()) {
                case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                    switch ($state) {
                        case self::TRANSACTION_STATE_OPEN:
                            return true;
                        default:
                            throw new Creativestyle_AmazonPayments_Exception('Invalid Order Reference status');
                    }
                case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                    switch ($state) {
                        case self::TRANSACTION_STATE_PENDING:
                        case self::TRANSACTION_STATE_OPEN:
                        case self::TRANSACTION_STATE_CLOSED:
                            return true;
                        case self::TRANSACTION_STATE_DECLINED:
                            switch ($reason) {
                                case self::TRANSACTION_REASON_INVALID_PAYMENT:
                                    throw new Creativestyle_AmazonPayments_Exception_InvalidStatus_Recoverable('Invalid Authorization status');
                                case self::TRANSACTION_REASON_TIMEOUT:
                                case self::TRANSACTION_REASON_AMAZON_REJECTED:
                                    throw new Creativestyle_AmazonPayments_Exception_InvalidStatus('Invalid Authorization status');
                            }
                        default:
                            throw new Creativestyle_AmazonPayments_Exception('Invalid Authorization status');
                    }
                case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                    switch ($state) {
                        case self::TRANSACTION_STATE_PENDING:
                        case self::TRANSACTION_STATE_COMPLETED:
                            return true;
                        default:
                            throw new Creativestyle_AmazonPayments_Exception('Invalid Capture status');
                    }
                case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                    default:
                        return true;
                        //throw new Creativestyle_AmazonPayments_Exception('Invalid Refund status');
                default:
                    throw new Creativestyle_AmazonPayments_Exception('Invalid transaction type');
            }
        }
        throw new Creativestyle_AmazonPayments_Exception('Invalid transaction status');
    }

}
