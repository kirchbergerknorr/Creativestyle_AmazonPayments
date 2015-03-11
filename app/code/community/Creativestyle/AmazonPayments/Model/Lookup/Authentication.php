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
class Creativestyle_AmazonPayments_Model_Lookup_Authentication extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const POPUP_EXPERIENCE      = 'popup';
    const REDIRECT_EXPERIENCE   = 'redirect';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array('value' => self::POPUP_EXPERIENCE, 'label' => Mage::helper('amazonpayments')->__('Pop-up')),
                array('value' => self::REDIRECT_EXPERIENCE, 'label' => Mage::helper('amazonpayments')->__('Redirect')),
            );
        }
        return $this->_options;
    }
}
