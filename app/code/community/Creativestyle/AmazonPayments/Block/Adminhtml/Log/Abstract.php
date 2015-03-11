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
abstract class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Abstract extends Mage_Adminhtml_Block_Widget_Grid_Container {

    protected $_logType = null;

    public function __construct() {
        parent::__construct();
        $this->_blockGroup = 'amazonpayments';
        $this->_removeButton('add');
    }

    protected function _prepareLayout() {
        if (null !== $this->_logType) {
            $logFilePath = Creativestyle_AmazonPayments_Model_Logger::getAbsoluteLogFilePath($this->_logType);
            if (file_exists($logFilePath)) {
                $this->_addButton('download', array(
                    'label'     => $this->_getDownloadButtonLabel(),
                    'onclick'   => 'setLocation(\'' . $this->_getDownloadUrl() .'\')',
                    'class'     => 'scalable'
                ), -1);
            } else {
                $this->_addButton('download', array(
                    'label'     => $this->_getDownloadButtonLabel(),
                    'onclick'   => 'setLocation(\'' . $this->_getDownloadUrl() .'\')',
                    'class'     => 'scalable',
                    'disabled'  => true
                ), -1);
            }
        }
        return parent::_prepareLayout();
    }

    protected function _getDownloadButtonLabel() {
        return $this->__('Download as CSV');
    }

    protected function _getDownloadUrl() {
        return $this->getUrl('*/*/download');
    }

    public function getHeaderCssClass() {
        return 'head-amazonpayments-log ' . parent::getHeaderCssClass();
    }

}
