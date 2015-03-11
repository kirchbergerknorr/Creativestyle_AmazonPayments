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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_View_Abstract extends Mage_Adminhtml_Block_Widget_Container {

    /**
     * Instance of the log model
     *
     * @var Varien_Object $_model
     */
    protected $_model = null;

    public function __construct() {
        parent::__construct();
        $this->_addButton('back', array(
            'label'     => Mage::helper('adminhtml')->__('Back'),
            'onclick'   => 'window.location.href=\'' . $this->getUrl('*/*/') . '\'',
            'class'     => 'back',
        ));
    }

    /**
     * Returns log model instance
     *
     * @return Varien_Object
     */
    protected function _getLog() {
        return $this->_model;
    }

    /**
     * Converts field names for geters
     *
     * @param string $name
     * @return string
     */
    protected function _underscore($name) {
        return strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
    }

    public function __call($method, $args) {
        if (null !== $this->_getLog()) {
            if (substr($method, 0, 3) == 'get') {
                $key = $this->_underscore(substr($method,3));
                return $this->_getLog()->getData($key);
            }
        }
    }

    public function getTimestamp() {
        if (null !== $this->_getLog()) {
            return Mage::app()->getLocale()->date($this->_getLog()->getTimestamp());
        }
        return null;
    }

    public function setLog($model) {
        $this->_model = $model;
        return $this;
    }

    public function getHeaderCssClass() {
        return 'icon-head head-amazonpayments-log ' . parent::getHeaderCssClass();
    }

}
