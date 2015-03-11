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
class Creativestyle_AmazonPayments_Block_Adminhtml_CredentialsValidator extends Mage_Adminhtml_Block_System_Config_Form_Field {

    public function render(Varien_Data_Form_Element_Abstract $element) {
        $html = '<td class="label"></td>';
        $html .= '<td class="value">';
        $html .= '<div id="amazonAccountValidation"></div>';
        $html .= '<button title="' . $this->__('Validate Amazon Payments account'). '" type="button" class="scalable save" onclick="amazonAccountValidate()" style=""><span><span><span>' . $this->__('Validate Amazon Payments account'). '</span></span></span></button>';
        $html .= '<script type="text/javascript">//<![CDATA[' . "\n";
        $html .= '    function amazonAccountValidate() {' . "\n";
        $html .= '        new Ajax.Updater(\'amazonAccountValidation\', \'' . Mage::helper('adminhtml')->getUrl('admin_amazonpayments/adminhtml_system/validate') . '\', {' . "\n";
        $html .= '            parameters: {' . "\n";
        $html .= '                merchantId: $F(\'amazonpayments_account_merchant_id\'),' . "\n";
        $html .= '                accessKey: $F(\'amazonpayments_account_access_key\'),' . "\n";
        $html .= '                secretKey: $F(\'amazonpayments_account_secret_key\'),' . "\n";
        $html .= '                region: $F(\'amazonpayments_account_region\'),' . "\n";
        $html .= '                sandbox: $F(\'amazonpayments_general_sandbox\')' . "\n";
        $html .= '            }' . "\n";
        $html .= '        });' . "\n";
        $html .= '    }' . "\n";
        $html .= '//]]></script>' . "\n";

        if ($element->getComment()) {
            $html .= '<p class="note"><span>'.$element->getComment().'</span></p>';
        }
        $html .= '</td>';

        if ($element->getCanUseWebsiteValue() || $element->getCanUseDefaultValue()) {
            $html .= '<td rowspan="2" class="use-default"></td>';
        }
        $html .= '<td rowspan="2" class="scope-label"></td>';
        $html .= '<td rowspan="2" class=""></td>';

        // Magento 1.5, 1.6 and 1.7.0.0 backward compatibility
        if (!method_exists($this, '_decorateRowHtml')) {
            return '<tr id="row_' . $element->getHtmlId() . '">' . $html . '</tr>';
        }

        return $this->_decorateRowHtml($element, $html);
    }

}
