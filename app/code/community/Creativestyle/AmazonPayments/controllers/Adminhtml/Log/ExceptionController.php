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
class Creativestyle_AmazonPayments_Adminhtml_Log_ExceptionController extends Mage_Adminhtml_Controller_Action {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getCollection() {
        return Mage::getModel('amazonpayments/log_collection')->setLogType('exception');
    }

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('creativestyle/amazonpayments/log/exception')
            ->_addBreadcrumb($this->__('Pay with Amazon'), $this->__('Pay with Amazon'))
            ->_addBreadcrumb($this->__('Log preview'), $this->__('Log preview'))
            ->_addBreadcrumb($this->__('Exceptions'), $this->__('Exceptions'));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__('Pay with Amazon'))->_title($this->__('Log preview'))->_title($this->__('Exceptions'));
        $this->_initAction()
            ->renderLayout();
    }

    public function viewAction() {
        $id = $this->getRequest()->getParam('id');
        $log = $this->_getCollection()->getItemById($id);
        if (is_object($log) && $log->getId()) {
            $this->_title($this->__('Pay with Amazon'))->_title($this->__('Log preview'))->_title($this->__('Exceptions'))->_title($this->__('Preview'));
            $this->_initAction();
            $this->_addContent($this->getLayout()->createBlock('amazonpayments/adminhtml_log_exception_view')->setLog($log));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('amazonpayments')->__('Log does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function downloadAction() {
        $logFilePath = Creativestyle_AmazonPayments_Model_Logger::getAbsoluteLogFilePath('exception');
        if (file_exists($logFilePath)) {
            $output = implode($this->_getConfig()->getLogDelimiter(), Creativestyle_AmazonPayments_Model_Logger::getColumnMapping('exception')) . "\n";
            $output .= file_get_contents($logFilePath);
            Mage::app()->getResponse()->setHeader('Content-type', 'text/csv');
            Mage::app()->getResponse()->setHeader('Content-disposition', 'attachment;filename=' . basename($logFilePath) . '.csv');
            Mage::app()->getResponse()->setHeader('Content-Length', filesize($logFilePath));
            Mage::app()->getResponse()->setBody($output);
        } else {
            $this->_redirect('*/*/');
        }
    }

}
