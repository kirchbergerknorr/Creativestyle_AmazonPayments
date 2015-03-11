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
class Creativestyle_AmazonPayments_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {

    const ACTION_MANUAL               = 'manual';
    const ACTION_AUTHORIZE            = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE    = 'authorize_capture';
    const ACTION_ERP                  = 'erp';

    const CHECK_USE_FOR_COUNTRY       = 1;
    const CHECK_USE_FOR_CURRENCY      = 2;
    const CHECK_USE_CHECKOUT          = 4;
    const CHECK_USE_FOR_MULTISHIPPING = 8;
    const CHECK_USE_INTERNAL          = 16;
    const CHECK_ORDER_TOTAL_MIN_MAX   = 32;
    const CHECK_RECURRING_PROFILES    = 64;
    const CHECK_ZERO_TOTAL            = 128;

}
