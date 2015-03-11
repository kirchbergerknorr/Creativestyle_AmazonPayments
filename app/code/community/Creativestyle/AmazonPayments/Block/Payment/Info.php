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
class Creativestyle_AmazonPayments_Block_Payment_Info extends Mage_Payment_Block_Info {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('creativestyle/amazonpayments/payment/info.phtml');
    }

    public function toPdf() {
        $this->setTemplate('creativestyle/amazonpayments/payment/pdf/info.phtml');
        return $this->toHtml();
    }

}
