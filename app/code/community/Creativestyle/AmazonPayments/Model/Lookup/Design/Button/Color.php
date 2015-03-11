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
class Creativestyle_AmazonPayments_Model_Lookup_Design_Button_Color extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const COLOR_ORANGE  = 'orange';
    const COLOR_TAN     = 'tan';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::COLOR_ORANGE, 'label' => Mage::helper('amazonpayments')->__('Orange (recommended)')),
                array('value' => self::COLOR_TAN, 'label' => Mage::helper('amazonpayments')->__('Tan')),
            );
        }
        return $this->_options;
    }
}
