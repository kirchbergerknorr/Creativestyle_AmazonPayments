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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Ipn extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_Abstract {

    protected $_logType = 'ipn';

    public function __construct() {
        parent::__construct();
        $this->_controller = 'adminhtml_log_ipn';
        $this->_headerText = Mage::helper('amazonpayments')->__('IPN Notifications');
    }

}
