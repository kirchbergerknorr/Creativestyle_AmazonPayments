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
class Creativestyle_AmazonPayments_Model_Lookup_IpnActive extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array(
                    'value' => 0,
                    'label' => Mage::helper('amazonpayments')->__('No, use data polling instead')
                ),
                array(
                    'value' => 1,
                    'label' => Mage::helper('adminhtml')->__('Yes')
                )
            );
        }
        return $this->_options;
    }

}
