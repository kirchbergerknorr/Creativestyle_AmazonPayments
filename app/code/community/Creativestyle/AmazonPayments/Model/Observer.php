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
class Creativestyle_AmazonPayments_Model_Observer {

    const DATA_POLL_TRANSACTION_LIMIT  = 36;
    const DATA_POLL_SLEEP_BETWEEN_TIME = 300000;



    // **********************************************************************
    // Object instances geters

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }



    // **********************************************************************
    // Transactions details fetching routines

    /**
     * Fetch details for the provided transaction
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     */
    protected function _fetchTransactionInfo($transaction, $shouldSave = true) {
        $transaction->getOrderPaymentObject()
            ->setOrder($transaction->getOrder())
            ->importTransactionInfo($transaction);
        if ($shouldSave) $transaction->save();
        return $transaction->getId();
    }

    protected function _pollTransactionData() {
        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addPaymentInformation(array('method'))
            ->addFieldToFilter('method', array('in' => Mage::helper('amazonpayments')->getAvailablePaymentMethods()))
            ->addFieldToFilter('is_closed', 0)
            ->setOrder('transaction_id', 'asc');

        $recentPolledTransaction = $this->_getConfig()->getRecentPolledTransaction();
        if ($recentPolledTransaction) {
            $collection->addFieldToFilter('transaction_id', array('gt' => (int)$recentPolledTransaction));
        }

        $collection->load();

        $recentTransactionId = null;
        $count = 0;
        $dateModel = Mage::getModel('core/date');

        foreach ($collection as $transaction) {
            try {
                $txnType = $transaction->getTxnType();
                switch (strtolower(Mage::helper('amazonpayments')->getTransactionStatus($transaction))) {
                    case 'pending':
                        $recentTransactionId = $this->_fetchTransactionInfo($transaction);
                        $count++;
                        usleep(self::DATA_POLL_SLEEP_BETWEEN_TIME);
                        break;
                    case 'suspended':
                        if ($txnType == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER) {
                            $recentTransactionId = $this->_fetchTransactionInfo($transaction);
                            $count++;
                            usleep(self::DATA_POLL_SLEEP_BETWEEN_TIME);
                        }
                        break;
                    case 'open':
                        $txnAge = floor(($dateModel->timestamp() - $dateModel->timestamp($transaction->getCreatedAt())) / (60 * 60 * 24));
                        if (($txnType == Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER && $txnAge > 180) ||
                            ($txnType == Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH && $txnAge > 30)) {
                            $recentTransactionId = $this->_fetchTransactionInfo($transaction);
                            $count++;
                            usleep(self::DATA_POLL_SLEEP_BETWEEN_TIME);
                        }
                        break;
                    case null:
                        $recentTransactionId = $this->_fetchTransactionInfo($transaction);
                        $count++;
                        usleep(self::DATA_POLL_SLEEP_BETWEEN_TIME);
                        break;

                }
                if ($count >= self::DATA_POLL_TRANSACTION_LIMIT) {
                    break;
                }
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
            }
        }

        if ($count < self::DATA_POLL_TRANSACTION_LIMIT) {
            $recentTransactionId = null;
        }

        $this->_getConfig()->setRecentPolledTransaction($recentTransactionId);

    }

    protected function _shouldUpdateParentTransaction($transaction) {
        $updateMatrix = array(
            'completed' => array(
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND
            ),
            'closed' => array(
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
            ),
            'declined' => array(
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND
            )
        );
        $txnStatus = strtolower(Mage::helper('amazonpayments')->getTransactionStatus($transaction));
        $txnType = $transaction->getTxnType();
        if (array_key_exists($txnStatus, $updateMatrix) && in_array($txnType, $updateMatrix[$txnStatus])) {
            return true;
        }
        return false;
    }

    protected function _updateParentTransaction($transaction, $shouldSave = true) {
        if ($this->_shouldUpdateParentTransaction($transaction)) {
            $parentTransaction = $transaction->getParentTransaction();
            if ($parentTransaction && !$parentTransaction->getIsClosed()) {
                $this->_fetchTransactionInfo($parentTransaction, $shouldSave);
            }
        }
        return $this;
    }

    protected function _updateOrderTransaction($transaction, $shouldSave = true) {
        if ($transaction->getTxnType() == Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE) {
            $txnStatus = strtolower(Mage::helper('amazonpayments')->getTransactionStatus($transaction));
            if (in_array($txnStatus, array('completed', 'closed'))) {
                $payment = $transaction->getOrderPaymentObject();
                if ($payment) {
                    $payment->getMethodInstance()
                        ->setOrder($transaction->getOrder())
                        ->closeOrderReference($payment);
                }
            }
        }
        return $this;
    }



    // **********************************************************************
    // Event observers

    /**
     * Inject Authorize button to the admin order view page
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function injectAuthorizeButton($observer) {
        try {
            $order = Mage::registry('sales_order');
            // check if object instance exists and whether manual authorization is enabled
            if (is_object($order) && $order->getId() && $this->_getConfig()->isManualAuthorizationAllowed()) {
                $payment = $order->getPayment();
                if (in_array($payment->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                    // check if payment wasn't authorized already
                    $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
                    $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    // invoke injectAuthorizeButton helper if authorization transaction does not exist or is closed
                    if ($orderTransaction && !$orderTransaction->getIsClosed() && (!$authTransaction || $authTransaction->getIsClosed())) {
                        $block = Mage::getSingleton('core/layout')->getBlock('sales_order_edit');
                        if ($block) {
                            $url = Mage::getModel('adminhtml/url')->getUrl('admin_amazonpayments/adminhtml_order/authorize', array('order_id' => $order->getId()));
                            $message = Mage::helper('amazonpayments')->__('Are you sure you want to authorize payment for this order?');
                            $block->addButton('payment_authorize', array(
                                'label'     => Mage::helper('amazonpayments')->__('Authorize payment'),
                                'onclick'   => "confirmSetLocation('{$message}', '{$url}')",
                                'class'     => 'go'
                            ));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return $this;
    }

    /**
     * Capture and log Amazon Payments API call
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function logApiCall($observer) {
        $callData = $observer->getEvent()->getCallData();
        if (is_array($callData)) {
            Creativestyle_AmazonPayments_Model_Logger::logApiCall($callData);
        }
        return $this;
    }

    /**
     * Capture and log incoming IPN notification
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function logIpnCall($observer) {
        $callData = $observer->getEvent()->getCallData();
        if (is_array($callData)) {
            Creativestyle_AmazonPayments_Model_Logger::logIpnCall($callData);
        }
        return $this;
    }


    public function closeTransaction($observer) {
        try {
            $transaction = $observer->getEvent()->getOrderPaymentTransaction();
            if ($transaction->getId() && in_array($transaction->getOrderPaymentObject()->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                $closeFor = array('canceled', 'closed', 'declined', 'completed');
                if (in_array(strtolower(Mage::helper('amazonpayments')->getTransactionStatus($transaction)), $closeFor)) {
                    $transaction->setIsClosed(true);
                } else {
                    $transaction->setIsClosed(false);
                }
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return $this;
    }

    public function updateParentTransaction($observer) {
        try {
            $transaction = $observer->getEvent()->getOrderPaymentTransaction();
            if ($transaction->getId() && in_array($transaction->getOrderPaymentObject()->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                $this->_updateParentTransaction($transaction);
                $this->_updateOrderTransaction($transaction);
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return $this;
    }

    public function setSecureUrls($observer) {
        $secureUrlsConfigNode = Mage::getConfig()->getNode('frontend/secure_url');
        if ($this->_getConfig()->isPopupAuthenticationExperience()) {
            $secureUrlsConfigNode->addChild('amazonpayments_cart', '/checkout/cart');
        }
        if ($this->_getConfig()->isSandbox()) {
            unset($secureUrlsConfigNode->amazonpayments_ipn);
        }
        return $this;
    }



    // **********************************************************************
    // Cronjobs

    /**
     * Invokes Amazon Payments logfiles rotating
     *
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function rotateLogfiles() {
        try {
            Creativestyle_AmazonPayments_Model_Logger::rotateLogfiles();
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            throw $e;
        }
        return $this;
    }

    /**
     * Invokes data polling from Amazon Payments gateway
     *
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function pollObjectsData() {
        try {
            if (!$this->_getConfig()->isIpnActive()) {
                $this->_pollTransactionData();
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            throw $e;
        }
        return $this;
    }

}
