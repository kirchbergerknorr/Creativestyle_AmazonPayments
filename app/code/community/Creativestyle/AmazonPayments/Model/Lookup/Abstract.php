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
class Creativestyle_AmazonPayments_Model_Lookup_Abstract extends Varien_Object {

    protected $_options = null;

    public function getOptions() {
        $result = array();
        $_options = $this->toOptionArray();
        foreach ($_options as $_option) {
            if (isset($_option['label']) && isset($_option['value']))
                $result[$_option['value']] = $_option['label'];
        }
        return $result;
    }

}
