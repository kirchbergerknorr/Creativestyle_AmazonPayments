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
class Creativestyle_AmazonPayments_Model_Lookup_Frequency extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const FREQUENCY_5_MINUTES       = '5 minutes';
    const FREQUENCY_10_MINUTES      = '10 minutes';
    const FREQUENCY_15_MINUTES      = '15 minutes';
    const FREQUENCY_30_MINUTES      = '30 minutes';
    const FREQUENCY_1_HOUR          = '1 hour';
    const FREQUENCY_2_HOURS         = '2 hours';
    const FREQUENCY_4_HOURS         = '4 hours';
    const FREQUENCY_8_HOURS         = '8 hours';
    const FREQUENCY_12_HOURS        = '12 hours';
    const FREQUENCY_1_DAY           = '1 day';
    const FREQUENCY_2_DAYS          = '2 days';
    const FREQUENCY_3_DAYS          = '3 days';
    const FREQUENCY_7_DAYS          = '7 days';
    const FREQUENCY_14_DAYS         = '14 days';
    const FREQUENCY_15_DAYS         = '15 days';
    const FREQUENCY_30_DAYS         = '30 days';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array(
                    'value' => 5 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_5_MINUTES)
                ),
                array(
                    'value' => 10 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_10_MINUTES)
                ),
                array(
                    'value' => 15 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_15_MINUTES)
                ),
                array(
                    'value' => 30 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_30_MINUTES)
                ),
                array(
                    'value' => 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_1_HOUR)
                ),
                array(
                    'value' => 2 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_2_HOURS)
                ),
                array(
                    'value' => 4 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_4_HOURS)
                ),
                array(
                    'value' => 8 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_8_HOURS)
                ),
                array(
                    'value' => 12 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_12_HOURS)
                ),
                array(
                    'value' => 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_1_DAY)
                ),
                array(
                    'value' => 2 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_2_DAYS)
                ),
                array(
                    'value' => 3 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_3_DAYS)
                ),
                array(
                    'value' => 7 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_7_DAYS)
                ),
                array(
                    'value' => 14 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_14_DAYS)
                ),
                array(
                    'value' => 15 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_15_DAYS)
                ),
                array(
                    'value' => 30 * 24 * 60 * 60,
                    'label' => Mage::helper('amazonpayments')->__(self::FREQUENCY_30_DAYS)
                )
            );
        }
        return $this->_options;
    }

}
