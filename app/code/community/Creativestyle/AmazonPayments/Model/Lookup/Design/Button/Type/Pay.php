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
class Creativestyle_AmazonPayments_Model_Lookup_Design_Button_Type_Pay extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const TYPE_FULL     = 'PwA';
    const TYPE_SHORT    = 'Pay';
    const TYPE_LOGO     = 'A';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::TYPE_FULL, 'label' => Mage::helper('amazonpayments')->__('Pay with Amazon')),
                array('value' => self::TYPE_SHORT, 'label' => Mage::helper('amazonpayments')->__('Pay')),
                array('value' => self::TYPE_LOGO, 'label' => Mage::helper('amazonpayments')->__('Amazon logo'))
            );
        }
        return $this->_options;
    }
}
