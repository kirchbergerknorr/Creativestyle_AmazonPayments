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
class Creativestyle_AmazonPayments_Model_Lookup_Design_Button_Size_LoginPay extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const SIZE_SMALL    = 'small';
    const SIZE_MEDIUM   = 'medium';
    const SIZE_LARGE    = 'large';
    const SIZE_XLARGE   = 'x-large';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::SIZE_SMALL, 'label' => Mage::helper('amazonpayments')->__('Small')),
                array('value' => self::SIZE_MEDIUM, 'label' => Mage::helper('amazonpayments')->__('Medium')),
                array('value' => self::SIZE_LARGE, 'label' => Mage::helper('amazonpayments')->__('Large')),
                array('value' => self::SIZE_XLARGE, 'label' => Mage::helper('amazonpayments')->__('X-Large'))
            );
        }
        return $this->_options;
    }

}
