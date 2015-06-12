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
class Creativestyle_AmazonPayments_Model_Api_Advanced extends Creativestyle_AmazonPayments_Model_Api_Abstract {

    protected function _getApi() {
        if (null === $this->_api) {
            $this->_api = new OffAmazonPaymentsService_Client($this->_getConfig()->getConnectionData(null, $this->_store));
        }
        return $this->_api;
    }

    public function getOrderReferenceDetails($orderReferenceId) {
        $request = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId
        ));
        $response = $this->_getApi()->getOrderReferenceDetails($request);
        if ($response->isSetGetOrderReferenceDetailsResult()) {
            $result = $response->getGetOrderReferenceDetailsResult();
            if ($result->isSetOrderReferenceDetails()) {
                return $result->getOrderReferenceDetails();
            }
        }
        return null;
    }

    public function setOrderReferenceDetails($orderReferenceId, $orderAmount, $orderCurrency, $magentoOrderId = null) {
        $request = new OffAmazonPaymentsService_Model_SetOrderReferenceDetailsRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId,
            'OrderReferenceAttributes' => array(
                'PlatformId' => 'AIOVPYYF70KB5',
                'OrderTotal' => array(
                    'Amount' => $orderAmount,
                    'CurrencyCode' => $orderCurrency
                ),
                'SellerOrderAttributes' => ($magentoOrderId ? array('SellerOrderId' => $magentoOrderId) : null)
            )
        ));
        $response = $this->_getApi()->setOrderReferenceDetails($request);
        if ($response->isSetSetOrderReferenceDetailsResult()) {
            $result = $response->getSetOrderReferenceDetailsResult();
            if ($result->isSetOrderReferenceDetails()) {
                return $result->getOrderReferenceDetails();
            }
        }
        return null;
    }

    public function confirmOrderReference($orderReferenceId) {
        $request = new OffAmazonPaymentsService_Model_ConfirmOrderReferenceRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId
        ));
        $response = $this->_getApi()->confirmOrderReference($request);
        return $response;
    }

    public function cancelOrderReference($orderReferenceId) {
        $request = new OffAmazonPaymentsService_Model_CancelOrderReferenceRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId
        ));
        $response = $this->_getApi()->cancelOrderReference($request);
        return $response;
    }

    public function closeOrderReference($orderReferenceId, $closureReason = null) {
        $request = new OffAmazonPaymentsService_Model_CloseOrderReferenceRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId,
        ));
        if (null !== $closureReason) {
            $request->setClosureReason($closureReason);
        }
        $response = $this->_getApi()->closeOrderReference($request);
        return $response;
    }

    public function authorize($orderReferenceId, $authorizationReferenceId, $authorizationAmount, $authorizationCurrency, $sellerAuthorizationNote = null, $captureNow = false, $transactionTimeout = null) {
        $request = new OffAmazonPaymentsService_Model_AuthorizeRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonOrderReferenceId' => $orderReferenceId,
            'AuthorizationReferenceId' => $authorizationReferenceId,
            'AuthorizationAmount' => array(
                'Amount' => $authorizationAmount,
                'CurrencyCode' => $authorizationCurrency
            ),
            'CaptureNow' => $captureNow
        ));
        if (null !== $sellerAuthorizationNote) {
            $request->setSellerAuthorizationNote($sellerAuthorizationNote);
        }
        if (null !== $transactionTimeout) {
            $request->setTransactionTimeout($transactionTimeout);
        }
        $response = $this->_getApi()->authorize($request);
        if ($response->isSetAuthorizeResult()) {
            $result = $response->getAuthorizeResult();
            if ($result->isSetAuthorizationDetails()) {
                return $result->getAuthorizationDetails();
            }
        }
        return null;
    }

    public function getAuthorizationDetails($authorizationId) {
        $request = new OffAmazonPaymentsService_Model_GetAuthorizationDetailsRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonAuthorizationId' => $authorizationId
        ));
        $response = $this->_getApi()->getAuthorizationDetails($request);
        if ($response->isSetGetAuthorizationDetailsResult()) {
            $result = $response->getGetAuthorizationDetailsResult();
            if ($result->isSetAuthorizationDetails()) {
                return $result->getAuthorizationDetails();
            }
        }
        return null;
    }

    public function capture($authorizationReferenceId, $captureReferenceId, $captureAmount, $captureCurrency, $sellerCaptureNote = null) {
        $request = new OffAmazonPaymentsService_Model_CaptureRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonAuthorizationId' => $authorizationReferenceId,
            'CaptureReferenceId' => $captureReferenceId,
            'CaptureAmount' => array(
                'Amount' => $captureAmount,
                'CurrencyCode' => $captureCurrency
            )
        ));
        if (null !== $sellerCaptureNote) {
            $request->setSellerCaptureNote($sellerCaptureNote);
        }
        $response = $this->_getApi()->capture($request);
        if ($response->isSetCaptureResult()) {
            $result = $response->getCaptureResult();
            if ($result->isSetCaptureDetails()) {
                return $result->getCaptureDetails();
            }
        }
        return null;
    }

    public function getCaptureDetails($captureId) {
        $request = new OffAmazonPaymentsService_Model_GetCaptureDetailsRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonCaptureId' => $captureId
        ));
        $response = $this->_getApi()->getCaptureDetails($request);
        if ($response->isSetGetCaptureDetailsResult()) {
            $result = $response->getGetCaptureDetailsResult();
            if ($result->isSetCaptureDetails()) {
                return $result->getCaptureDetails();
            }
        }
        return null;
    }

    public function refund($captureReferenceId, $refundReferenceId, $refundAmount, $refundCurrency, $sellerRefundNote = null) {
        $request = new OffAmazonPaymentsService_Model_RefundRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonCaptureId' => $captureReferenceId,
            'RefundReferenceId' => $refundReferenceId,
            'RefundAmount' => array(
                'Amount' => $refundAmount,
                'CurrencyCode' => $refundCurrency
            )
        ));
        if (null !== $sellerRefundNote) {
            $request->setSellerRefundNote($sellerRefundNote);
        }
        $response = $this->_getApi()->refund($request);
        if ($response->isSetRefundResult()) {
            $result = $response->getRefundResult();
            if ($result->isSetRefundDetails()) {
                return $result->getRefundDetails();
            }
        }
        return null;
    }

    public function getRefundDetails($refundId) {
        $request = new OffAmazonPaymentsService_Model_GetRefundDetailsRequest(array(
            'SellerId' => $this->getMerchantId(),
            'AmazonRefundId' => $refundId
        ));
        $response = $this->_getApi()->getRefundDetails($request);
        if ($response->isSetGetRefundDetailsResult()) {
            $result = $response->getGetRefundDetailsResult();
            if ($result->isSetRefundDetails()) {
                return $result->getRefundDetails();
            }
        }
        return null;
    }

}
