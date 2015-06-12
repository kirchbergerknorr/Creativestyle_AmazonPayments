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
 * @copyright  Copyright (c) 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Lookup_AuthorizationMode extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const ASYNCHRONOUS = 'asynchronous';
    const SYNCHRONOUS  = 'synchronous';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::ASYNCHRONOUS, 'label' => Mage::helper('amazonpayments')->__('Asynchronous')),
                array('value' => self::SYNCHRONOUS, 'label' => Mage::helper('amazonpayments')->__('Synchronous')),
            );
        }
        return $this->_options;
    }
}
