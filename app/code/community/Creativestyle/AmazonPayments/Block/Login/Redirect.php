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
 * @copyright  Copyright (c) 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Login_Redirect extends Creativestyle_AmazonPayments_Block_Login_Abstract {

    public function getAccessTokenParamName() {
        if ($this->hasData('access_token_param_name')) {
            return $this->getData('access_token_param_name');
        }
        return 'access_token';
    }

    public function getRedirectUrl() {
        if ($this->hasData('redirect_url')) {
            return $this->getData('redirect_url');
        }
        return $this->getFailureUrl();
    }

    public function getFailureUrl() {
        if ($this->hasData('failure_url')) {
            return $this->getData('failure_url');
        }
        return $this->getUrl('customer/account/login');
    }

}
