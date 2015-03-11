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
class Creativestyle_AmazonPayments_Block_Adminhtml_IpnUrl extends Mage_Adminhtml_Block_System_Config_Form_Field {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        if (Mage::app()->isSingleStoreMode() || strtolower($element->getScope()) == 'stores') {
            $storeId = strtolower($element->getScope()) == 'stores' ? $element->getScopeId() : null;
            $urlParams = array(
                '_current' => false,
                '_nosid' => true,
                '_store' => $storeId,
                '_forced_secure' => !$this->_getConfig()->isSandbox($storeId)
            );
            if ($this->_getConfig()->isSandbox($storeId)) {
                $urlParams['_secure'] = false;
            }
            $url = Mage::getModel('core/url')->setStore($storeId)->getUrl('amazonpayments/advanced_ipn/', $urlParams);
            return sprintf('<a class="nobr" href="%s">%s</a>', $url, $url) . '<input type="hidden" id="' . $element->getHtmlId() . '"/>';
        } else {
            return sprintf('<span style="font-weight:bold;color:red;">%s</span>', $this->__('Select appropriate store view scope to display IPN endpoint URL')) . '<input type="hidden" id="' . $element->getHtmlId() . '"/>';
        }
        return '<input type="hidden" id="' . $element->getHtmlId() . '"/>';
    }

}
