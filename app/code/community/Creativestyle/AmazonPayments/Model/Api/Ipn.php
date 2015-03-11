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
class Creativestyle_AmazonPayments_Model_Api_Ipn extends Creativestyle_AmazonPayments_Model_Api_Abstract {

    const NOTIFICATION_TYPE_ORDER_REFERENCE = 'OrderReferenceNotification';
    const NOTIFICATION_TYPE_AUTHORIZATION   = 'AuthorizationNotification';
    const NOTIFICATION_TYPE_CAPTURE         = 'CaptureNotification';
    const NOTIFICATION_TYPE_REFUND          = 'RefundNotification';

    protected function _getApi() {
        if (null === $this->_api) {
            $this->_api = new OffAmazonPaymentsNotifications_Client();
        }
        return $this->_api;
    }

    public function parseMessage($headers, $body) {
        return $this->_getApi()->parseRawMessage($headers, $body);
    }

}
