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
class Creativestyle_AmazonPayments_Advanced_IpnController extends Mage_Core_Controller_Front_Action {

    protected $_headers = array('x-amz-sns-message-type', 'x-amz-sns-message-id', 'x-amz-sns-topic-arn', 'x-amz-sns-subscription-arn');

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _sendResponse($code = 500, $message = null) {
        $response = $this->getResponse();
        switch ($code) {
            case 200:
                $responseCode = '200 OK';
                break;
            case 403:
                $responseCode = '403 Forbidden';
                break;
            case 404:
                $responseCode = '404 Not Found';
                break;
            case 503:
                $responseCode = '503 Service Unavailable';
                break;
            default:
                $responseCode = '500 Internal Server Error';
                break;
        }
        $response->setHeader('HTTP/1.1', $responseCode);
        if ($message) $response->setBody($message);
        $response->sendResponse();
    }

    public function indexAction() {
        if ($this->_getConfig()->isPaymentProcessingAllowed() && $this->_getConfig()->isIpnActive()) {
            try {
                $headers = array();
                $requestHeaders = array();
                foreach ($this->_headers as $headerId) {
                    if (Mage::app()->getRequest()->getHeader($headerId)) {
                        $headers[$headerId] = Mage::app()->getRequest()->getHeader($headerId);
                        $requestHeaders[] = $headerId . ': ' . Mage::app()->getRequest()->getHeader($headerId);
                    }
                }

                $notification = Mage::getSingleton('amazonpayments/api_ipn')->parseMessage($headers, Mage::app()->getRequest()->getRawBody());
                Mage::dispatchEvent('amazonpayments_advanced_ipn_request', array(
                    'call_data' => array(
                        'notification_type' => $notification->getNotificationType(),
                        'response_code' => 200,
                        'response_error' => null,
                        'request_headers' => implode("\n", $requestHeaders),
                        'request_body' => Mage::app()->getRequest()->getRawBody()
                    )
                ));
                Mage::getSingleton('amazonpayments/processor_ipn')->processNotification($notification);
                $this->_sendResponse(200);
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $this->_sendResponse(503, $e->getMessage());
            }
        } else {
            $this->_forward('noRoute');
        }
    }

}
