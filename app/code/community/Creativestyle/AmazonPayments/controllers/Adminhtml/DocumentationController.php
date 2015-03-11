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
class Creativestyle_AmazonPayments_Adminhtml_DocumentationController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('amazonpayments/documentation')
            ->_addBreadcrumb($this->__('Pay with Amazon'), $this->__('Pay with Amazon'))
            ->_addBreadcrumb($this->__('Documentation'), $this->__('Documentation'));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__('Pay with Amazon'))->_title($this->__('Documentation'));
        $this->_initAction()->renderLayout();
    }

}
