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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Exception_View extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_View_Abstract {

    public function __construct() {
        parent::__construct();
        $this->_controller = 'adminhtml_log_exception';
        $this->_headerText = $this->__('Pay with Amazon Exception');
        $this->setTemplate('creativestyle/amazonpayments/advanced/log/exception/view.phtml');
    }

    public function setLog($model) {
        parent::setLog($model);
        if (is_object($model) && $model->getId()) {
            $this->_headerText = $this->__('Pay with Amazon Exception | %s',
                $this->getTimestamp()
            );
        }
        return $this;
    }

}
