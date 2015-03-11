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
class Creativestyle_AmazonPayments_Adminhtml_DebugController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('creativestyle/amazonpayments/debug')
            ->_addBreadcrumb($this->__('Pay with Amazon'), $this->__('Pay with Amazon'))
            ->_addBreadcrumb($this->__('Debug data'), $this->__('Debug data'));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__('Pay with Amazon'))->_title($this->__('Debug data'));
        $this->_initAction()
            ->renderLayout();
    }

    public function downloadAction() {
        $debugData = Mage::helper('amazonpayments/debug')->getDebugData();
        $filename = str_replace(array('.', '/', '\\'), array('_'), parse_url(Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL), PHP_URL_HOST)) .
            '_apa_debug_' . Mage::getModel('core/date')->gmtTimestamp() . '.dmp';
        $debugData = base64_encode(serialize($debugData));
        Mage::app()->getResponse()->setHeader('Content-type', 'application/base64');
        Mage::app()->getResponse()->setHeader('Content-disposition', 'attachment;filename=' . $filename);
        Mage::app()->getResponse()->setBody($debugData);
    }

}
