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
class Creativestyle_AmazonPayments_Block_Adminhtml_Debug extends Mage_Adminhtml_Block_Template {

    public function __construct() {
        parent::__construct();
        $this->setTemplate('creativestyle/amazonpayments/debug.phtml');
    }

    protected function _prepareLayout() {
        $accordion = $this->getLayout()->createBlock('adminhtml/widget_accordion')->setId('amazonPaymentsDebug');

        $accordion->addItem('general', array(
            'title'     => Mage::helper('amazonpayments')->__('General Info'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section')->setDebugArea('general')->toHtml()
        ));

        $accordion->addItem('stores', array(
            'title'     => Mage::helper('amazonpayments')->__('Stores'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('stores')->toHtml()
        ));

        $accordion->addItem('amazon_account', array(
            'title'     => Mage::helper('amazonpayments')->__('Amazon Payments Account'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('amazon_account')->toHtml()
        ));

        $accordion->addItem('amazon_general', array(
            'title'     => Mage::helper('amazonpayments')->__('General Settings'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('amazon_general')->toHtml()
        ));

        $accordion->addItem('amazon_email', array(
            'title'     => Mage::helper('amazonpayments')->__('Email Options'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('amazon_email')->toHtml()
        ));

        $accordion->addItem('amazon_design', array(
            'title'     => Mage::helper('amazonpayments')->__('Appearance Settings'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('amazon_design')->toHtml()
        ));

        $accordion->addItem('amazon_developer', array(
            'title'     => Mage::helper('amazonpayments')->__('Developer Options'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('amazon_developer')->toHtml()
        ));

        $accordion->addItem('magento_general', array(
            'title'     => Mage::helper('amazonpayments')->__('Magento Settings'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('magento_general')->toHtml()
        ));

        $accordion->addItem('cronjobs', array(
            'title'     => Mage::helper('amazonpayments')->__('Amazon Cronjobs'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section_table')->setDebugArea('cronjobs')->setShowKeys(false)->toHtml()
        ));

        $accordion->addItem('event_observers', array(
            'title'     => Mage::helper('amazonpayments')->__('Event Observers'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section')->setDebugArea('event_observers')->toHtml()
        ));

        $accordion->addItem('magento_extensions', array(
            'title'     => Mage::helper('amazonpayments')->__('Installed Magento extensions'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section')->setDebugArea('magento_extensions')->toHtml()
        ));

        $accordion->addItem('php_modules', array(
            'title'     => Mage::helper('amazonpayments')->__('Installed PHP modules'),
            'content'   => $this->getLayout()->createBlock('amazonpayments/adminhtml_debug_section')->setDebugArea('php_modules')->toHtml()
        ));

        $this->setChild('debug_data', $accordion);
        return parent::_prepareLayout();
    }

    public function getDownloadUrl() {
        return $this->getUrl('*/*/download');
    }
}
