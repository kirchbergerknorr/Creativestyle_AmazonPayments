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
class Creativestyle_AmazonPayments_Block_Onepage_Button extends Creativestyle_AmazonPayments_Block_Abstract {

    /**
     * Render Amazon Payments button
     *
     * @return string
     */
    protected function _toHtml() {
        try {
            if ($this->_isActive()) {
                if ($this->isLoginActive()) {
                    $button = $this->getChild('onepage.amazonpayments.button.login');
                } else {
                    $button = $this->getChild('onepage.amazonpayments.button.pay');
                }
                if ($button) {
                    return $button->toHtml();
                }
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return parent::_toHtml();
    }

}
