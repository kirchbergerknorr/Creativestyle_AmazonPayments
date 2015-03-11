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
class Creativestyle_AmazonPayments_Model_Manager {


    // **********************************************************************
    // Object instances geters

    protected function _getApi() {
        return Mage::getSingleton('amazonpayments/api_advanced');
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }



    // **********************************************************************
    // General helpers

    protected function _sanitizeReferenceId($referenceId) {
        return substr($referenceId, 0, strrpos($referenceId, '-'));
    }

    protected function _lookupPayment($referenceId) {
        $order = Mage::getModel('sales/order')->loadByAttribute('ext_order_id', $referenceId);
        if (is_object($order) && $order->getId()) return $order->getPayment();
        return null;
    }

    /**
     * Check if addresses differ, return false otherwise
     *
     * @param Mage_Customer_Model_Address_Abstract $address1st
     * @param array $address2nd
     * @return bool
     */
    protected function _compareAddresses($address1st, $address2nd) {
        // compare both addresses, but streets due their array nature in a separate call
        $streetDiff = array_diff($address2nd['street'], $address1st->getStreet());
        return ((isset($address2nd['firstname']) && $address1st->getFirstname() != $address2nd['firstname'])
            || (isset($address2nd['lastname']) && $address1st->getLastname() != $address2nd['lastname'])
            || (isset($address2nd['company']) && $address1st->getCompany() != $address2nd['company'])
            || (isset($address2nd['city']) && $address1st->getCity() != $address2nd['city'])
            || (isset($address2nd['postcode']) && $address1st->getPostcode() != $address2nd['postcode'])
            || (isset($address2nd['country_id']) && $address1st->getCountryId() != $address2nd['country_id'])
            || (!empty($streetDiff)));
    }

    /**
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param array $newAddress
     * @return bool
     */
    protected function _updateOrderAddress(Mage_Customer_Model_Address_Abstract $address, $newAddress) {
        if ($this->_compareAddresses($address, $newAddress)) {
            if (isset($newAddress['firstname'])) {
                $address->setFirstname($newAddress['firstname']);
            }
            if (isset($newAddress['lastname'])) {
                $address->setLastname($newAddress['lastname']);
            }
            if (isset($newAddress['company'])) {
                $address->setCompany($newAddress['company']);
            }
            if (isset($newAddress['street'])) {
                $address->setStreet($newAddress['street']);
            }
            if (isset($newAddress['city'])) {
                $address->setCity($newAddress['city']);
            }
            if (isset($newAddress['postcode'])) {
                $address->setPostcode($newAddress['postcode']);
            }
            if (isset($newAddress['country_id'])) {
                $address->setCountryId($newAddress['country_id']);
            }
            if (isset($newAddress['telephone'])) {
                $address->setTelephone($newAddress['telephone']);
            }
            return true;
        }
        return false;
    }

    /**
     * Check if emails differ, return false otherwise
     *
     * @param string $email1st
     * @param string $email2nd
     * @return bool
     */
    protected function _compareEmails($email1st, $email2nd) {
        return trim($email1st) != trim($email2nd);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $newEmail
     * @return bool
     */
    protected function _updateCustomerEmail(Mage_Sales_Model_Order $order, $newEmail) {
        if ($this->_compareEmails($order->getCustomerEmail(), $newEmail)) {
            $order->setCustomerEmail(trim($newEmail));
            return true;
        }
        return false;
    }


    /**
     * Check if names differ, return false otherwise
     *
     * @param string $name1st
     * @param string $name2nd
     * @return bool
     */
    protected function _compareNames($name1st, $name2nd) {
        return trim($name1st) != trim($name2nd);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $newEmail
     * @return bool
     */
    protected function _updateCustomerName(Mage_Sales_Model_Order $order, $newName) {
        $customerNameUpdated = false;
        if (isset($newName['firstname']) && isset($newName['lastname'])) {
            if ($this->_compareNames($order->getCustomerFirstname(), $newName['firstname'])) {
                $order->setCustomerFirstname($newName['firstname']);
                $customerNameUpdated = true;
            }
            if ($this->_compareNames($order->getCustomerLastname(), $newName['lastname'])) {
                $order->setCustomerLastname($newName['lastname']);
                $customerNameUpdated = true;
            }
        }
        return $customerNameUpdated;
    }

    /**
     * Check whether provided address lines contain PO Box data
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
     * Convert Amazon AddressLine fields to the array
     * Try to guess if address lines contain company name or PO Box
     *
     * @param string $addressLine1
     * @param string $addressLine2
     * @param string $addressLine3
     * @param string $countryId
     *
     * @return array
     */
    protected function _convertAmazonAddressLinesToArray($addressLine1, $addressLine2 = null, $addressLine3 = null, $countryId = null) {
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
     * Converts Amazon address object to the array
     *
     * @return OffAmazonPaymentsService_Model_Address $amazonAddress
     * @return array
     */
    protected function _convertAmazonAddressToArray($amazonAddress) {
        $address = $this->_convertAmazonAddressLinesToArray(
            $amazonAddress->getAddressLine1(),
            $amazonAddress->getAddressLine2(),
            $amazonAddress->getAddressLine3(),
            $amazonAddress->getCountryCode()
        );
        if ($amazonAddress->isSetName()) {
            $customerName = Mage::helper('amazonpayments')->explodeCustomerName($amazonAddress->getName());
            $address['firstname'] = $customerName->getFirstname();
            $address['lastname'] = $customerName->getLastname();
        }
        if ($amazonAddress->isSetCity()) {
            $address['city'] = $amazonAddress->getCity();
        }
        if ($amazonAddress->isSetPostalCode()) {
            $address['postcode'] = $amazonAddress->getPostalCode();
        }
        if ($amazonAddress->isSetCountryCode()) {
            $address['country_id'] = $amazonAddress->getCountryCode();
        }
        if ($amazonAddress->isSetPhone()) {
            $address['telephone'] = $amazonAddress->getPhone();
        }
        return $address;
    }



    // **********************************************************************
    // General handling routines

    /**
     * Update state of Magento transaction object
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_Status|OffAmazonPaymentsNotifications_Model_Status $transactionStatus
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _updateTransactionStatus($transaction, $transactionStatus) {
        if ($transactionStatus->isSetState()) {
            if (Mage::helper('amazonpayments')->getTransactionStatus($transaction) != $transactionStatus->getState()) {
                $statusArray = array('State' => $transactionStatus->getState());
                if ($transactionStatus->isSetReasonCode()) {
                    $statusArray['ReasonCode'] = $transactionStatus->getReasonCode();
                }
                if ($transactionStatus->isSetReasonDescription()) {
                    $statusArray['ReasonDescription'] = $transactionStatus->getReasonDescription();
                }
                return $statusArray;
            }
        }
        return null;
    }

/*
    protected function _updateTransactionStatus($transaction, $transactionStatus) {
        if ($transactionStatus->isSetState()) {
            $statusArray = array('State' => $transactionStatus->getState());
            if ($transactionStatus->isSetReasonCode()) {
                $statusArray['ReasonCode'] = $transactionStatus->getReasonCode();
            }
            if ($transactionStatus->isSetReasonDescription()) {
                $statusArray['ReasonDescription'] = $transactionStatus->getReasonDescription();
            }
            $transaction->setAdditionalInformation('state', strtolower($transactionStatus->getState()))
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $statusArray);
        }
        return $transaction;
    }
*/

    /**
     * 
     */
    protected function _addHistoryComment($order, $transaction, $amount, $state) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                $message = 'An order of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                $message = 'An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                $message = 'A capture of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                $message = 'A refund of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            default:
                throw new Creativestyle_AmazonPayments_Exception('Cannot add a history comment for unsupported transaction type.');
        }

        return $order->addStatusHistoryComment(Mage::helper('amazonpayments')->__(
                $message,
                $order->getStore()->convertPrice($amount, true, false),
                $transaction->getTxnId(),
                strtoupper($state)
            ), true
        )->setIsCustomerNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
    }




    // **********************************************************************
    // Order Reference handling routines

    /**
     * Handle & process open Amazon's Order Reference object
     *
     * @todo $orderUpdated variable obsolete, remove it and add coments when order data changes
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleOpenOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($transaction && $orderReferenceDetails->isSetOrderReferenceStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus());
            if (null !== $newStatus) {
                $order = $payment->getOrder();
                $orderUpdated = false;

                // depending on the data source, some fields may be not available, process below section
                // only for responses to GetOrderReferenceDetails calls, skip for OrderReference notifications
                if ($orderReferenceDetails instanceof OffAmazonPaymentsService_Model_OrderReferenceDetails) {
                    if ($orderReferenceDetails->isSetBuyer()) {
                       if ($orderReferenceDetails->getBuyer()->isSetEmail()) {
                            $orderUpdated = $this->_updateCustomerEmail($order, $orderReferenceDetails->getBuyer()->getEmail()) || $orderUpdated;
                        }
                    }

                    if ($orderReferenceDetails->isSetDestination()) {
                        if ($orderReferenceDetails->getDestination()->isSetPhysicalDestination()) {
                            $shippingAddress = $this->_convertAmazonAddressToArray($orderReferenceDetails->getDestination()->getPhysicalDestination());
                            if (isset($shippingAddress['firstname']) && isset($shippingAddress['lastname'])) {
                                $customerName = array(
                                    'firstname' => $shippingAddress['firstname'],
                                    'lastname' => $shippingAddress['lastname']
                                );
                                $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
                            }
                            $orderUpdated = $this->_updateOrderAddress($order->getBillingAddress(), $shippingAddress) || $orderUpdated;
                            $orderUpdated = $this->_updateOrderAddress($order->getShippingAddress(), $shippingAddress) || $orderUpdated;
                        }
                    }
                }

                $this->_addHistoryComment($order, $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState());

                $transactionSave = Mage::getModel('core/resource_transaction');
                if ($orderUpdated) {
                    $transactionSave->addObject($order);
                    $transactionSave->addCommitCallback(array($order, 'save'));
                }

                // check if authorization should be re-submitted
                $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                if ($authTransaction && $authTransaction->getIsClosed() && ($order->getBaseTotalDue() > 0)) {
                    $payment->authorize(true, $order->getBaseTotalDue());
                    $transactionSave->addObject($payment);
                    $orderUpdated = true;
                }

                if ($orderUpdated) {
                    $transactionSave->save();
                }

            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process suspended Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleSuspendedOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process canceled Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleCanceledOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process closed Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleClosedOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }



    // **********************************************************************
    // Authorization handling routines

    /**
     * Handle & process pending Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handlePendingAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $authorizationDetails->getAuthorizationAmount()->getAmount(), $authorizationDetails->getAuthorizationStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process open Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleOpenAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus());
            if (null !== $newStatus) {
                $order = $payment->getOrder();
                $orderUpdated = false;

                if ($authorizationDetails->isSetAuthorizationBillingAddress()) {
                    $billingAddress = $this->_convertAmazonAddressToArray($authorizationDetails->getAuthorizationBillingAddress());
                    if (isset($billingAddress['firstname']) && isset($billingAddress['lastname'])) {
                        $customerName = array(
                            'firstname' => $billingAddress['firstname'],
                            'lastname' => $billingAddress['lastname']
                        );
                        $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
                    }
                    $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
                    $orderUpdated = $this->_updateOrderAddress($order->getBillingAddress(), $billingAddress) || $orderUpdated;
                }

                if ($order->getStatus() != $this->_getConfig()->getAuthorizedOrderStatus()) {
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_PROCESSING,
                        $this->_getConfig()->getAuthorizedOrderStatus(),
                        Mage::helper('amazonpayments')->__('An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.',
                            $order->getStore()->convertPrice($authorizationDetails->getAuthorizationAmount()->getAmount(), true, false),
                            $authorizationDetails->getAmazonAuthorizationId(),
                            strtoupper($authorizationDetails->getAuthorizationStatus()->getState())
                        ),
                        Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE
                    );
                    $orderUpdated = true;
                }

                $transactionSave = Mage::getModel('core/resource_transaction');
                if ($orderUpdated) {
                    $transactionSave->addObject($order);
                    $transactionSave->addCommitCallback(array($order, 'save'));
                }

                if ($this->_getConfig()->captureImmediately() && $order->canInvoice()) {
                    $invoice = $order->prepareInvoice()
                        ->register()
                        ->capture();
                    $invoice->setTransactionId($authorizationDetails->getAmazonAuthorizationId());
                    $transactionSave->addObject($invoice);
                    $orderUpdated = true;
                }

                if ($orderUpdated) {
                    $transactionSave->save();
                }

                if (!$order->getEmailSent() && $this->_getConfig()->sendEmailConfirmation()) {
                    $order->sendNewOrderEmail();
                }

            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process declined Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleDeclinedAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus());
            if (null !== $newStatus) {
                if ($authorizationDetails->getAuthorizationStatus()->getReasonCode() == 'InvalidPaymentMethod') {
                    Mage::helper('amazonpayments')->sendAuthorizationDeclinedEmail($payment, $authorizationDetails);
                }
                $this->_addHistoryComment($payment->getOrder(), $transaction, $authorizationDetails->getAuthorizationAmount()->getAmount(), $authorizationDetails->getAuthorizationStatus()->getState())->setIsCustomerNotified(true)->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process closed Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleClosedAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $authorizationDetails->getAuthorizationAmount()->getAmount(), $authorizationDetails->getAuthorizationStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }



    // **********************************************************************
    // Capture handling routines

    /**
     * Handle & process pending Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handlePendingCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process declined Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleDeclinedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process completed Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleCompletedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process closed Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleClosedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }



    // **********************************************************************
    // Refund handling routines

    /**
     * Handle & process pending Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handlePendingRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $refundDetails->getRefundAmount()->getAmount(), $refundDetails->getRefundStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process declined Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handleDeclinedRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $refundDetails->getRefundAmount()->getAmount(), $refundDetails->getRefundStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }

    /**
     * Handle & process completed Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handleCompletedRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $newStatus = $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus());
            if (null !== $newStatus) {
                $this->_addHistoryComment($payment->getOrder(), $transaction, $refundDetails->getRefundAmount()->getAmount(), $refundDetails->getRefundStatus()->getState())->save();
            }
            return $newStatus;
        }
        return null;
    }



    // **********************************************************************
    // Public interface

    /**
     * Imports payment transaction details
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return null
     */
    public function importTransactionDetails($payment, $transaction) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                return $this->importOrderReferenceDetails(
                    $this->_getApi()->setStore($payment->getOrder()->getStoreId())->getOrderReferenceDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                return $this->importAuthorizationDetails(
                    $this->_getApi()->setStore($payment->getOrder()->getStoreId())->getAuthorizationDetails($transaction->getTxnId()),
                    $payment,
                    $transaction,
                    false
                );
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                return $this->importCaptureDetails(
                    $this->_getApi()->setStore($payment->getOrder()->getStoreId())->getCaptureDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                return $this->importRefundDetails(
                    $this->_getApi()->setStore($payment->getOrder()->getStoreId())->getRefundDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
        }
        return null;
    }

    /**
     * Import Amazon's Order Reference object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importOrderReferenceDetails($orderReferenceDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $orderReferenceDetails->isSetAmazonOrderReferenceId()) {
            $payment = $this->_lookupPayment($orderReferenceDetails->getAmazonOrderReferenceId());
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $orderReferenceDetails->getAmazonOrderReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
                );
            }
            if ($transaction && $orderReferenceDetails->isSetOrderReferenceStatus()) {
                $orderReferenceStatus = $orderReferenceDetails->getOrderReferenceStatus();
                if ($orderReferenceStatus->isSetState()) {
                    switch (strtolower($orderReferenceStatus->getState())) {
                        case 'open':
                            return $this->_handleOpenOrderReference($payment, $transaction, $orderReferenceDetails);
                        case 'suspended':
                            return $this->_handleSuspendedOrderReference($payment, $transaction, $orderReferenceDetails);
                        case 'canceled':
                            return $this->_handleCanceledOrderReference($payment, $transaction, $orderReferenceDetails);
                        case 'closed':
                            return $this->_handleClosedOrderReference($payment, $transaction, $orderReferenceDetails);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Import Amazon's Authorization object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param bool $refetchDetails
     */
    public function importAuthorizationDetails($authorizationDetails, $payment = null, $transaction = null, $refetchDetails = true) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $authorizationDetails->isSetAuthorizationReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($authorizationDetails->getAuthorizationReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $authorizationDetails->getAmazonAuthorizationId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH
                );
            }
            if ($transaction && $authorizationDetails->isSetAuthorizationStatus()) {
                $authorizationStatus = $authorizationDetails->getAuthorizationStatus();
                if ($authorizationStatus->isSetState()) {
                    switch (strtolower($authorizationStatus->getState())) {
                        case 'pending':
                            return $this->_handlePendingAuthorization($payment, $transaction, $authorizationDetails);
                        case 'open':
                            if ($refetchDetails) {
                                $authorizationDetails = $this->_getApi()->getAuthorizationDetails($authorizationDetails->getAmazonAuthorizationId());
                            }
                            return $this->_handleOpenAuthorization($payment, $transaction, $authorizationDetails);
                        case 'declined':
                            return $this->_handleDeclinedAuthorization($payment, $transaction, $authorizationDetails);
                        case 'closed':
                            return $this->_handleClosedAuthorization($payment, $transaction, $authorizationDetails);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Import Amazon's Capture object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importCaptureDetails($captureDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $captureDetails->isSetCaptureReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($captureDetails->getCaptureReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $captureDetails->getAmazonCaptureId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                );
            }
            if ($transaction && $captureDetails->isSetCaptureStatus()) {
                $captureStatus = $captureDetails->getCaptureStatus();
                if ($captureStatus->isSetState()) {
                    switch (strtolower($captureStatus->getState())) {
                        case 'pending':
                            return $this->_handlePendingCapture($payment, $transaction, $captureDetails);
                        case 'declined':
                            return $this->_handleDeclinedCapture($payment, $transaction, $captureDetails);
                        case 'completed':
                            return $this->_handleCompletedCapture($payment, $transaction, $captureDetails);
                        case 'closed':
                            return $this->_handleClosedCapture($payment, $transaction, $captureDetails);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Import Amazon's Refund object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importRefundDetails($refundDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $refundDetails->isSetRefundReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($refundDetails->getRefundReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $refundDetails->getAmazonRefundId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND
                );
            }
            if ($transaction && $refundDetails->isSetRefundStatus()) {
                $refundStatus = $refundDetails->getRefundStatus();
                if ($refundStatus->isSetState()) {
                    switch (strtolower($refundStatus->getState())) {
                        case 'pending':
                            return $this->_handlePendingRefund($payment, $transaction, $refundDetails);
                        case 'declined':
                            return $this->_handleDeclinedRefund($payment, $transaction, $refundDetails);
                        case 'completed':
                            return $this->_handleCompletedRefund($payment, $transaction, $refundDetails);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Process a notification message requested via IPN
     *
     * @param OffAmazonPaymentNotifications_Notification $notification
     */
    public function processNotification($notification = null) {
        if (null !== $notification) {
            switch ($notification->getNotificationType()) {
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_ORDER_REFERENCE:
                    if ($notification->isSetOrderReference()) {
                        $transactionStatus = $this->importOrderReferenceDetails($notification->getOrderReference());
                        if (null !== $transactionStatus) {
                            $payment = $this->_lookupPayment($notification->getOrderReference()->getAmazonOrderReferenceId());
                            if ($payment) {
                                $transaction = $payment->lookupTransaction(
                                    $notification->getOrderReference()->getAmazonOrderReferenceId(),
                                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
                                );
                                if ($transaction) {
                                    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
                                    $transaction->save();
                                }
                            }
                        }
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('OrderReference field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_AUTHORIZATION:
                    if ($notification->isSetAuthorizationDetails()) {
                        $transactionStatus = $this->importAuthorizationDetails($notification->getAuthorizationDetails());
                        if (null !== $transactionStatus) {
                            $referenceId = $this->_sanitizeReferenceId($notification->getAuthorizationDetails()->getAuthorizationReferenceId());
                            $payment = $this->_lookupPayment($referenceId);
                            if ($payment) {
                                $transaction = $payment->lookupTransaction(
                                    $notification->getAuthorizationDetails()->getAmazonAuthorizationId(),
                                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH
                                );
                                if ($transaction) {
                                    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
                                    $transaction->save();
                                }
                            }
                        }
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('AuthorizationDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_CAPTURE:
                    if ($notification->isSetCaptureDetails()) {
                        $transactionStatus = $this->importCaptureDetails($notification->getCaptureDetails());
                        if (null !== $transactionStatus) {
                            $referenceId = $this->_sanitizeReferenceId($notification->getCaptureDetails()->getCaptureReferenceId());
                            $payment = $this->_lookupPayment($referenceId);
                            if ($payment) {
                                $transaction = $payment->lookupTransaction(
                                    $notification->getCaptureDetails()->getAmazonCaptureId(),
                                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                                );
                                if ($transaction) {
                                    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
                                    $transaction->save();
                                }
                            }
                        }
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('CaptureDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_REFUND:
                    if ($notification->isSetRefundDetails()) {
                        $transactionStatus = $this->importRefundDetails($notification->getRefundDetails());
                        if (null !== $transactionStatus) {
                            $referenceId = $this->_sanitizeReferenceId($notification->getRefundDetails()->getRefundReferenceId());
                            $payment = $this->_lookupPayment($referenceId);
                            if ($payment) {
                                $transaction = $payment->lookupTransaction(
                                    $notification->getRefundDetails()->getAmazonRefundId(),
                                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND
                                );
                                if ($transaction) {
                                    $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
                                    $transaction->save();
                                }
                            }
                        }
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('RefundDetails field not found in submitted notification');
                    }
                    break;
                default:
                    throw new Creativestyle_AmazonPayments_Exception('Wrong Notification type');
            }
        } else {
            throw new Creativestyle_AmazonPayments_Exception('No notification data provided');
        }
    }

}
