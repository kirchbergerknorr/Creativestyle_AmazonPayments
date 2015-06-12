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
class Creativestyle_AmazonPayments_Model_Processor_Order {

    protected $_order = null;

    protected $_store = null;

    /**
     * Return Amazon Payments config model instance
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * TODO: [_initStateObject description]
     *
     * @param  Varien_Object &$stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _initStateObject(&$stateObject = null) {
        if (null === $stateObject) {
            $stateObject = new Varien_Object();
        }
        $stateObject->setData(array(
            'state' => $this->getOrder()->getState() ? $this->getOrder()->getState() : Mage_Sales_Model_Order::STATE_NEW,
            'status' => $this->getOrder()->getStatus() ? $this->getOrder()->getStatus() : $this->_getConfig()->getNewOrderStatus($this->_store),
            'is_notified' => Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE
        ));
        return $this;
    }

    /**
     * Check whether provided address lines contain PO Box data
     *
     * @param string $addressLine1
     * @param string|null $addressLine2
     *
     * @return bool
     */
    protected function _isPoBox($addressLine1, $addressLine2 = null) {
        if (is_numeric($addressLine1)) {
            return true;
        }
        if (strpos(strtolower($addressLine1), 'packstation') !== false) {
            return true;
        }
        if (strpos(strtolower($addressLine2), 'packstation') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Convert address object from Amazon Payments API response to Varien_Object
     * indexed with the same keys Magento order address entities
     *
     * @param OffAmazonPaymentsService_Model_Address $amazonAddress
     *
     * @return Varien_Object
     */
    protected function _mapAmazonAddress($amazonAddress) {
        $data = $this->_mapAmazonAddressLines(
            $amazonAddress->getAddressLine1(),
            $amazonAddress->getAddressLine2(),
            $amazonAddress->getAddressLine3(),
            $amazonAddress->getCountryCode()
        );
        $explodedName = Mage::helper('amazonpayments')->explodeCustomerName($amazonAddress->getName());
        $data['firstname'] = $explodedName->getFirstname();
        $data['lastname'] = $explodedName->getLastname();
        $data['country_id'] = $amazonAddress->getCountryCode();
        $data['city'] = $amazonAddress->getCity();
        $data['postcode'] = $amazonAddress->getPostalCode();
        $data['telephone'] = $amazonAddress->getPhone();
        return new Varien_Object($data);
    }

    /**
     * Convert Amazon AddressLine fields to the array indexed with the same
     * keys Magento order address entities are using. Try to guess if address
     * lines contain company name or PO Box
     *
     * @param string $addressLine1
     * @param string $addressLine2
     * @param string $addressLine3
     * @param string $countryId
     *
     * @return array
     */
    protected function _mapAmazonAddressLines($addressLine1, $addressLine2 = null, $addressLine3 = null, $countryId = null) {
        $data = array('street' => array());
        if ($countryId && in_array($countryId, array('DE', 'AT'))) {
            if ($addressLine3) {
                if ($this->_isPoBox($addressLine1, $addressLine2)) {
                    $data['street'][] = $addressLine1;
                    $data['street'][] = $addressLine2;
                } else {
                    $data['company'] = trim($addressLine1 . ' ' . $addressLine2);
                }
                $data['street'][] = $addressLine3;
            } else if ($addressLine2) {
                if ($this->_isPoBox($addressLine1)) {
                    $data['street'][] = $addressLine1;
                } else {
                    $data['company'] = $addressLine1;
                }
                $data['street'][] = $addressLine2;
            } else {
                $data['street'][] = $addressLine1;
            }
        } else {
            if ($addressLine1) {
                $data['street'][] = $addressLine1;
            }
            if ($addressLine2) {
                $data['street'][] = $addressLine2;
            }
            if ($addressLine3) {
                $data['street'][] = $addressLine3;
            }
        }
        return $data;
    }

    /**
     * Convert transaction details object to Varien_Object indexed
     * with the same keys as Magento order entity
     *
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return Varien_Object
     */
    protected function _mapTransactionDetails($transactionDetails) {
        $data = array();
        // OrderReferenceDetails from API response
        if ($transactionDetails instanceof OffAmazonPaymentsService_Model_OrderReferenceDetails) {
            if ($transactionDetails->isSetBuyer()) {
                $data['customer_email'] = $transactionDetails->getBuyer()->getEmail();
                $customerName = Mage::helper('amazonpayments')->explodeCustomerName($transactionDetails->getBuyer()->getName(), null);
                $data['customer_firstname'] = $customerName->getFirstname();
                $data['customer_lastname'] = $customerName->getLastname();
            }
            if ($transactionDetails->isSetDestination()) {
                if ($transactionDetails->getDestination()->isSetPhysicalDestination()) {
                    $data['shipping_address'] = $this->_mapAmazonAddress($transactionDetails->getDestination()->getPhysicalDestination());
                }
            }
            if ($transactionDetails->isSetBillingAddress()) {
                if ($transactionDetails->getBillingAddress()->isSetPhysicalAddress()) {
                    $data['billing_address'] = $this->_mapAmazonAddress($transactionDetails->getBillingAddress()->getPhysicalAddress());
                    $data['customer_firstname'] = $data['billing_address']->getFirstname();
                    $data['customer_lastname'] = $data['billing_address']->getLastname();
                }
            } elseif (isset($data['shipping_address'])) {
                $data['billing_address'] = $data['shipping_address'];
            }
        }
        // AuthorizationDetails from API response
        elseif ($transactionDetails instanceof OffAmazonPaymentsService_Model_AuthorizationDetails) {
            if ($transactionDetails->isSetAuthorizationBillingAddress()) {
                $data['billing_address'] = $this->_mapAmazonAddress($transactionDetails->getAuthorizationBillingAddress());
                $data['customer_firstname'] = $data['billing_address']->getFirstname();
                $data['customer_lastname'] = $data['billing_address']->getLastname();
            }
        }
        return new Varien_Object($data);
    }

    /**
     * TODO: [_mapTransactionStatus description]
     *
     * @param  Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter $transactionAdapter
     * @param  Varien_Object &$stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _mapTransactionStatus($transactionAdapter, &$stateObject) {
        switch ($transactionAdapter->getTransactionType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                $message = 'An order of %s has been processed by Amazon Payments (%s). The new status is %s.';
                switch ($transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_KEY)) {
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_SUSPENDED:
                        $stateObject->setData(array(
                            'hold_before_state' => $stateObject->getState(),
                            'hold_before_status' => $stateObject->getStatus(),
                            'state' => Mage_Sales_Model_Order::STATE_HOLDED,
                            'status' => $this->_getConfig()->getHoldedOrderStatus($this->_store)
                        ));
                        break; // ORDER_SUSPENDED
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_OPEN:
                        $stateObject->setData(array(
                            'state' => Mage_Sales_Model_Order::STATE_NEW,
                            'status' => $this->_getConfig()->getNewOrderStatus($this->_store),
                        ));
                        break; // ORDER_OPEN
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_CANCELED:
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_CLOSED:
                        break; // ORDER_CANCELED / ORDER_CLOSED
                }
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                $message = 'An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.';
                switch ($transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_KEY)) {
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_PENDING:
                        $stateObject->setData(array(
                            'state' => Mage_Sales_Model_Order::STATE_NEW,
                            'status' => $this->_getConfig()->getNewOrderStatus($this->_store),
                        ));
                        break; // AUTH_PENDING
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_OPEN:
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_CLOSED:
                        $stateObject->setData(array(
                            'state' => Mage_Sales_Model_Order::STATE_PROCESSING,
                            'status' => $this->_getConfig()->getAuthorizedOrderStatus($this->_store)
                        ));
                        break; // AUTH_OPEN
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_DECLINED:
                        $stateObject->setData(array(
                            'hold_before_state' => $stateObject->getState(),
                            'hold_before_status' => $stateObject->getStatus(),
                            'state' => Mage_Sales_Model_Order::STATE_HOLDED,
                            'status' => $this->_getConfig()->getHoldedOrderStatus($this->_store)
                        ));
                        break; // AUTH_DECLINED
                }
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                $message = 'A capture of %s has been processed by Amazon Payments (%s). The new status is %s.';
                switch ($transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_KEY)) {
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_DECLINED:
                        $stateObject->setData(array(
                            'hold_before_state' => $stateObject->getState(),
                            'hold_before_status' => $stateObject->getStatus(),
                            'state' => Mage_Sales_Model_Order::STATE_HOLDED,
                            'status' => $this->_getConfig()->getHoldedOrderStatus($this->_store)
                        ));
                        $this->_cancelInvoice($transactionAdapter);
                        break; // CAPTURE_DECLINED
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_COMPLETED:
                        $stateObject->setData(array(
                            'state' => Mage_Sales_Model_Order::STATE_PROCESSING,
                            'status' => $this->_getConfig()->getAuthorizedOrderStatus($this->_store)
                        ));
                        break; // CAPTURE_COMPLETED
                }
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                $message = 'A refund of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            default:
                return $this;
        }

        $stateObject->setMessage(Mage::helper('amazonpayments')->__($message,
            $this->getOrder()->getBaseCurrency()->formatTxt($transactionAdapter->getTransactionAmount()),
            $transactionAdapter->getTransactionId(),
            sprintf('<strong>%s</strong>', strtoupper($transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_KEY)))
        ));

        return $this;
    }

    /**
     * TODO: [_sendTransactionEmails description]
     *
     * @param  Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter $transactionAdapter
     * @param  Varien_Object &$stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _sendTransactionEmails($transactionAdapter, &$stateObject) {
        switch ($transactionAdapter->getTransactionType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                switch ($transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_KEY)) {
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_OPEN:
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_CLOSED:
                        if ($this->getOrder() && !$this->getOrder()->getEmailSent() && $this->_getConfig()->sendEmailConfirmation($this->_store)) {
                            $this->getOrder()->sendNewOrderEmail();
                            $stateObject->setIsNotified(true);
                        }
                        break;
                    case Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_STATE_DECLINED:
                        if ($this->getOrder()
                            && $transactionAdapter->getStatusChange(Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_REASON_CODE_KEY)
                            == Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter::TRANSACTION_REASON_INVALID_PAYMENT)
                        {
                            Mage::helper('amazonpayments')->sendAuthorizationDeclinedEmail($this->getOrder());
                            $stateObject->setIsNotified(true);
                        }
                        break;
                }
                break;
            default:
                return $this;
        }

        return $this;
    }

    /**
     * TODO: [_updateAddress description]
     *
     * @param Mage_Customer_Model_Address_Abstract $addressObject
     * @param Varien_Object $addressData
     */
    protected function _updateAddress($addressObject, $addressData) {
        if ($addressObject->getFirstname() != $addressData->getFirstname()) {
            $addressObject->setFirstname($addressData->getFirstname());
        }
        if ($addressObject->getLastname() != $addressData->getLastname()) {
            $addressObject->setLastname($addressData->getLastname());
        }
        if ($addressObject->getCompany() != $addressData->getCompany()) {
            $addressObject->setCompany($addressData->getCompany());
        }
        if ($addressObject->getCity() != $addressData->getCity()) {
            $addressObject->setCity($addressData->getCity());
        }
        if ($addressObject->getPostcode() != $addressData->getPostcode()) {
            $addressObject->setPostcode($addressData->getPostcode());
        }
        if ($addressObject->getCountryId() != $addressData->getCountryId()) {
            $addressObject->setCountryId($addressData->getCountryId());
        }
        if ($addressObject->getTelephone() != $addressData->getTelephone()) {
            $addressObject->setTelephone($addressData->getTelephone());
        }
        $streetDiff = array_diff($addressObject->getStreet(), $addressData->getStreet());
        if (!empty($streetDiff)) {
            $addressObject->setStreet($addressData->getStreet());
        }
    }

    /**
     * TODO: [_updateOrderData description]
     *
     * @param  Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter $transactionAdapter
     * @param  Varien_Object &$stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _updateOrderData($transactionAdapter, &$stateObject) {
        $orderData = $this->_mapTransactionDetails($transactionAdapter->getTransactionDetails());
        if ($orderData->hasCustomerEmail() && $this->getOrder()->getCustomerEmail() != $orderData->getCustomerEmail()) {
            $this->getOrder()->setCustomerEmail($orderData->getCustomerEmail());
        }
        if ($orderData->hasCustomerFirstname() && $this->getOrder()->getCustomerFirstname() != $orderData->getCustomerFirstname()) {
            $this->getOrder()->setCustomerFirstname($orderData->getCustomerFirstname());
        }
        if ($orderData->hasCustomerLastname() && $this->getOrder()->getCustomerLastname() != $orderData->getCustomerLastname()) {
            $this->getOrder()->setCustomerLastname($orderData->getCustomerLastname());
        }
        if ($orderData->hasBillingAddress()) {
            $this->_updateAddress($this->getOrder()->getBillingAddress(), $orderData->getBillingAddress());
        }
        if ($orderData->hasShippingAddress()) {
            $this->_updateAddress($this->getOrder()->getShippingAddress(), $orderData->getShippingAddress());
        }

        $this->_mapTransactionStatus($transactionAdapter, $stateObject);

        $this->_sendTransactionEmails($transactionAdapter, $stateObject);

        if ($stateObject->getState() != $this->getOrder()->getState() || $stateObject->getStatus() != $this->getOrder()->getStatus()) {
            $this->getOrder()
                ->setHoldBeforeState($stateObject->getHoldBeforeState() ? $stateObject->getHoldBeforeState() : null)
                ->setHoldBeforeStatus($stateObject->getHoldBeforeStatus() ? $stateObject->getHoldBeforeStatus() : null)
                ->setState(
                    $stateObject->getState(),
                    $stateObject->getStatus(),
                    $stateObject->getMessage(),
                    $stateObject->getIsNotified()
                );
        } else if ($stateObject->getMessage()) {
            $this->getOrder()->addStatusHistoryComment($stateObject->getMessage());
        }

        return $this;
    }

    /**
     * TODO: [_getInvoiceForTransaction description]
     *
     * @param  Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return Mage_Sales_Model_Order_Invoice|bool
     */
    protected function _getInvoiceForTransaction($transaction) {
        foreach ($this->getOrder()->getInvoiceCollection() as $invoice) {
            if ($invoice->getTransactionId() == $transaction->getTxnId()) {
                $invoice->load($invoice->getId());
                return $invoice;
            }
        }
        return false;
    }

    /**
     * TODO: [_cancelInvoice description]
     *
     * @param  Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter $transactionAdapter
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    protected function _cancelInvoice($transactionAdapter) {
        $invoice = $this->_getInvoiceForTransaction($transactionAdapter->getTransaction());
        if ($invoice) {
            $invoice->cancel()->save();
        }
        return $this;
    }

    /**
     * TODO: [setOrder description]
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    public function setOrder($order) {
        $this->_order = $order;
        $this->_store = $order->getStoreId();
        return $this;
    }

    /**
     * TODO: [getOrder description]
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder() {
        if (null === $this->_order) {
            throw new Creativestyle_AmazonPayments_Exception('Order object is not set');
        }
        return $this->_order;
    }

    /**
     * TODO: [importTransactionDetails description]
     *
     * @param  Creativestyle_AmazonPayments_Model_Processor_TransactionAdapter $transactionAdapter
     * @param  Varien_Object &$stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    public function importTransactionDetails($transactionAdapter, &$stateObject) {
        $this->_initStateObject($stateObject)->_updateOrderData($transactionAdapter, $stateObject);
        $relatedTransactionAdapter = $transactionAdapter->processRelatedObjects($this->getOrder());
        if (null !== $relatedTransactionAdapter) {
            $this->_initStateObject($stateObject)->_updateOrderData($relatedTransactionAdapter, $stateObject);
        }
        return $this;
    }

    /**
     * TODO: [saveOrder description]
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Order
     */
    public function saveOrder() {
        if ($this->getOrder()->hasDataChanges()) {
            $this->getOrder()->save();
        }
        return $this;
    }

}
