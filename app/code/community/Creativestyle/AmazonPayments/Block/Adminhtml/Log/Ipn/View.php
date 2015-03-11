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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Ipn_View extends Creativestyle_AmazonPayments_Block_Adminhtml_Log_View_Abstract {

    public function __construct() {
        parent::__construct();
        $this->_controller = 'adminhtml_log_ipn';
        $this->_headerText = $this->__('IPN Notification');
        $this->setTemplate('creativestyle/amazonpayments/advanced/log/notification/view.phtml');
    }

    public function getRequestBody() {
        if (null !== $this->_getLog()) {
            if ($this->_getLog()->getRequestBody()) {
                try {
                    return Zend_Json::prettyPrint(stripslashes($this->_getLog()->getRequestBody()), array('indent' => '    '));
                } catch (Exception $e) {}
            }
        }
        return null;
    }

    public function setLog($model) {
        parent::setLog($model);
        if (is_object($model) && $model->getId()) {
            $this->_headerText = $this->__('IPN %s | %s',
                ($model->getNotificationType() ? $model->getNotificationType() : 'Notification'),
                $this->getTimestamp()
            );
        }
        return $this;
    }

}
