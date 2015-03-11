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
class Creativestyle_AmazonPayments_Adminhtml_SystemController extends Mage_Adminhtml_Controller_Action {

    protected function _checkCredentials($merchantId, $accessKey, $secretKey, $region, $sandbox) {
        if (!($merchantId && $accessKey && $secretKey)) {
            return array('error-msg', $this->__('Merchant ID, Access Key ID and Secret Key are required for the validation.'));
        }

        $result = array('success-msg', $this->__('Congratulations! Your Amazon Payments account settings seem to be OK.'));

        try {
            $api = new OffAmazonPaymentsService_Client(array(
                'merchantId' => trim($merchantId),
                'accessKey' => trim($accessKey),
                'secretKey' => trim($secretKey),
                'applicationName' => 'Creativestyle Amazon Payments Advanced Magento Extension',
                'applicationVersion' => Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version'),
                'region' => $region,
                'environment' => $sandbox ? 'sandbox' : 'live',
                'serviceURL' => '',
                'widgetURL' => '',
                'caBundleFile' => '',
                'clientId' => ''
            ));
            $apiRequest = new OffAmazonPaymentsService_Model_GetOrderReferenceDetailsRequest(array(
                'SellerId' => trim($merchantId),
                'AmazonOrderReferenceId' => 'P00-0000000-0000000'
            ));
            $api->getOrderReferenceDetails($apiRequest);
        } catch (OffAmazonPaymentsService_Exception $e) {
            switch ($e->getErrorCode()) {
                case 'InvalidOrderReferenceId':
                    break;
                case 'InvalidParameterValue':
                    $result = array('error-msg', $this->__('Whoops! Your Merchant ID seems to be invalid.'));
                    break;
                case 'InvalidAccessKeyId':
                    $result = array('error-msg', $this->__('Whoops! Your Access Key ID seems to be invalid.'));
                    break;
                case 'SignatureDoesNotMatch':
                    $result = array('error-msg', $this->__('Whoops! Your Secret Access Key seems to be invalid.'));
                    break;
                default:
                    $result = array('error-msg', $this->__('Whoops! Something went wrong while validating your account.'));
                    break;
            }
        } catch (Exception $ex) {
            Mage::logException($ex);
            $result = array('error-msg', $this->__('Whoops! Something went wrong while validating your account.'));
        }
        return $result;
    }

    public function validateAction() {
        $merchantId = $this->getRequest()->getPost('merchantId', null);
        $accessKey = $this->getRequest()->getPost('accessKey', null);
        $secretKey = $this->getRequest()->getPost('secretKey', null);
        $region = $this->getRequest()->getPost('region', null);
        $sandbox = $this->getRequest()->getPost('sandbox', null);

        $response = vsprintf(
            '<ul class="messages">
                <li class="%s">
                    <ul>
                        <li><span>%s</span></li>
                    </ul>
                </li>
            </ul>', $this->_checkCredentials($merchantId, $accessKey, $secretKey, $region, $sandbox));

        $this->getResponse()->setBody($response);
    }

}
