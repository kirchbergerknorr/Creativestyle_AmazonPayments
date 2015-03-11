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
class Creativestyle_AmazonPayments_Model_Lookup_PaymentAction extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array(
                    'value' => Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_MANUAL,
                    'label' => Mage::helper('amazonpayments')->__('Manual authorization')
                ),
                array(
                    'value' => Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE,
                    'label' => Mage::helper('amazonpayments')->__('Authorize')
                ),
                array(
                    'value' => Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_AUTHORIZE_CAPTURE,
                    'label' => Mage::helper('amazonpayments')->__('Authorize & capture')
                ),
                array(
                    'value' => Creativestyle_AmazonPayments_Model_Payment_Abstract::ACTION_ERP,
                    'label' => Mage::helper('amazonpayments')->__('ERP mode')
                )
            );
        }
        return $this->_options;
    }

}

