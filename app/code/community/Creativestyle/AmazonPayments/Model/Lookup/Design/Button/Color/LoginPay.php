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
class Creativestyle_AmazonPayments_Model_Lookup_Design_Button_Color_LoginPay extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const COLOR_GOLD      = 'Gold';
    const COLOR_DARK_GRAY = 'DarkGray';
    const COLOR_LIGHT_GRAY= 'LightGray';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::COLOR_GOLD, 'label' => Mage::helper('amazonpayments')->__('Gold')),
                array('value' => self::COLOR_DARK_GRAY, 'label' => Mage::helper('amazonpayments')->__('Dark gray')),
                array('value' => self::COLOR_LIGHT_GRAY, 'label' => Mage::helper('amazonpayments')->__('Light gray'))
            );
        }
        return $this->_options;
    }

}
