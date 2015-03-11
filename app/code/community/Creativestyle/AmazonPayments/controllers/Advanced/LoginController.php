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
class Creativestyle_AmazonPayments_Advanced_LoginController extends Mage_Core_Controller_Front_Action {

    const ACCESS_TOKEN_PARAM_NAME = 'access_token';

    private function _extractAccessTokenFromUrl() {
        return $this->getRequest()->getParam(self::ACCESS_TOKEN_PARAM_NAME, null);
    }

    private function _getApi() {
        return Mage::getModel('amazonpayments/api_login');
    }

    private function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    private function _getCustomerSession() {
        return Mage::getSingleton('customer/session');
    }

    protected function _getRedirectUrl() {
        return Mage::getUrl('*/*/index', array(
            self::ACCESS_TOKEN_PARAM_NAME => '%s',
            'target' => $this->getRequest()->getParam('target', null)
        ));
    }

    protected function _getRedirectFailureUrl() {
        if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
            return Mage::getUrl('checkout/cart');
        }
        return Mage::getUrl('customer/account/login');
    }
    protected function _getTargetUrl() {
        if (strtolower($this->getRequest()->getParam('target', null)) == 'checkout') {
            $accessToken = $this->_extractAccessTokenFromUrl();
            return Mage::getUrl('amazonpayments/advanced_checkout/', array('accessToken' => $accessToken));
        }
        return Mage::getUrl('customer/account/');
    }

    private function _validateUserProfile($userProfile) {
        return $userProfile instanceof Varien_Object && $userProfile->getEmail() && $userProfile->getName() && $userProfile->getUserId();
    }

    private function _validateAuthToken($authToken) {
        return $authToken instanceof Varien_Object && $authToken->getAud() != '';
    }

    public function preDispatch() {
        parent::preDispatch();
        if (!($this->_getConfig()->isActive() & Creativestyle_AmazonPayments_Model_Config::LOGIN_WITH_AMAZON_ACTIVE)) {
            $this->_forward('noRoute');
        }
    }

    public function indexAction() {
        $accessToken = $this->_extractAccessTokenFromUrl();
        if (null !== $accessToken) {
            $accessToken = urldecode($accessToken);
            try {
                $tokenInfo = $this->_getApi()->getTokenInfo($accessToken);
                if ($this->_validateAuthToken($tokenInfo)) {
                    $userProfile = $this->_getApi()->getUserProfile($accessToken);
                    if ($this->_validateUserProfile($userProfile)) {
                        $loginService = Mage::getModel('amazonpayments/service_login', $userProfile);
                        $connectStatus = $loginService->connect();
                        switch ($connectStatus->getStatus()) {
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_OK:
                                $this->_getCustomerSession()->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                $this->_redirectUrl($this->_getTargetUrl());
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_CONFIRM:
                                $loginPost = $this->getRequest()->getPost('login', array());
                                if (!empty($loginPost) && array_key_exists('password', $loginPost)) {
                                    if ($connectStatus->getCustomer()->validatePassword($loginPost['password'])) {
                                        $connectStatus->getCustomer()->setAmazonUserId($userProfile->getUserId())->save();
                                        $this->_getCustomerSession()->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                        $this->_redirectUrl($this->_getTargetUrl());
                                        return;
                                    } else {
                                        $this->_getCustomerSession()->addError($this->__('Invalid password'));
                                    }
                                }
                                $update = $this->getLayout()->getUpdate();
                                $update->addHandle('default');
                                $this->addActionLayoutHandles();
                                $update->addHandle('amazonpayments_account_confirm');
                                $this->loadLayoutUpdates();
                                $this->generateLayoutXml()->generateLayoutBlocks();
                                $this->_initLayoutMessages('customer/session');
                                $formBlock = $this->getLayout()->getBlock('amazonpayments_login_account_confirm');
                                if ($formBlock) {
                                    $formBlock->setData('back_url', $this->_getRefererUrl());
                                    $formBlock->setUsername($connectStatus->getCustomer()->getEmail());
                                }
                                $this->renderLayout();
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_DATA_MISSING:
                                $accountPost = $this->getRequest()->getPost('account', array());
                                if ($connectStatus->getRequiredData() && !empty($accountPost)) {
                                    $requiredData = $connectStatus->getRequiredData();
                                    $postedData = array();
                                    foreach ($accountPost as $attribute => $value) {
                                        if ($value) {
                                            $postedData[] = $attribute;
                                        }
                                    }
                                    $dataDiff = array_diff($requiredData, $postedData);
                                    if (empty($dataDiff)) {
                                        $connectStatus = $loginService->connect($accountPost);
                                        if ($connectStatus->getStatus() == Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_OK) {
                                            $this->_getCustomerSession()->setCustomerAsLoggedIn($connectStatus->getCustomer());
                                            $this->_redirectUrl($this->_getTargetUrl());
                                            return;
                                        } else {
                                            $this->_getCustomerSession()->addError($this->__('Please provide all required data.'));
                                        }
                                    } else {
                                        $this->_getCustomerSession()->addError($this->__('Please provide all required data.'));
                                    }
                                }
                                $update = $this->getLayout()->getUpdate();
                                $update->addHandle('default');
                                $this->addActionLayoutHandles();
                                $update->addHandle('amazonpayments_account_update');
                                $this->loadLayoutUpdates();
                                $this->generateLayoutXml()->generateLayoutBlocks();
                                $this->_initLayoutMessages('customer/session');
                                $formBlock = $this->getLayout()->getBlock('amazonpayments_login_account_update');
                                if ($formBlock) {
                                    $formBlock->setData('back_url', $this->_getRefererUrl());
                                    $formBlock->setFieldNameFormat('account[%s]');
                                    $formData = new Varien_Object($accountPost);
                                    if (!$formData->getFirstname() || !$formData->getLastname()) {
                                        $customerName = Mage::helper('amazonpayments')->explodeCustomerName($userProfile->getName());
                                        if (!$formData->getFirstname()) {
                                            $formData->setData('firstname', $customerName->getFirstname());
                                        }
                                        if (!$formData->getLastname()) {
                                            $formData->setData('lastname', $customerName->getLastname());
                                        }
                                    }
                                    $formBlock->setFormData($formData);
                                }
                                $this->renderLayout();
                                return;
                            case Creativestyle_AmazonPayments_Model_Service_Login::ACCOUNT_STATUS_ERROR:
                                throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Error when connecting accounts');
                        }
                    }
                    throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Retrieved user profile is invalid');
                }
                throw new Creativestyle_AmazonPayments_Exception('[LWA-controller] Provided access_token is invalid');
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $this->_getCustomerSession()->addError($this->__('There was an error connecting your Amazon account. Please contact us or try again later.'));
                $this->_redirectReferer();
                return;
            }
        }
        $this->_forward('noRoute');
    }

    public function redirectAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Login with Amazon'));
        $this->getLayout()->getBlock('amazonpayments_login_redirect')
            ->setAccessTokenParamName(self::ACCESS_TOKEN_PARAM_NAME)
            ->setRedirectUrl($this->_getRedirectUrl())
            ->setFailureUrl($this->_getRedirectFailureUrl());
        $this->renderLayout();
    }

}
