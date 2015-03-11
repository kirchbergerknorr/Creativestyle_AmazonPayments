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
class Creativestyle_AmazonPayments_Model_System_Config_Backend_DataPolling_Cron extends Mage_Core_Model_Config_Data {

    const XML_PATH_DATA_POLLING_CRON_EXPR = 'crontab/jobs/amazonpayments_advanced_data_poll/schedule/cron_expr';

    protected function _afterSave() {
        $frequency = $this->getData('groups/general/fields/polling_frequency/value');

        $months = floor($frequency / (30 * 24 * 60 * 60));
        $days = floor(($frequency - $months * 30 * 24 * 60 * 60) / (24 * 60 * 60));
        $hours = floor(($frequency - $months * 30 * 24 * 60 * 60 - $days * 24 * 60 * 60) / (60 * 60));
        $minutes = floor(($frequency - $months * 30 * 24 * 60 * 60 - $days * 24 * 60 * 60 - $hours * 60 * 60) / 60);

        $cronExpr = '*/5 * * * *';

        if ($months) {
            $cronExpr = '0 0 1 * *';
        } else if ($days) {
            $cronExpr = sprintf('0 0 *%s * *', ($days > 1 ? '/' . $days : ''));
        } else if ($hours) {
            $cronExpr = sprintf('0 *%s * * *', ($hours > 1 ? '/' . $hours : ''));
        } else if ($minutes) {
            $cronExpr = sprintf('*/%s * * * *', $minutes);
        }

        try {
            Mage::getModel('core/config_data')
                ->load(self::XML_PATH_DATA_POLLING_CRON_EXPR, 'path')
                ->setValue($cronExpr)
                ->setPath(self::XML_PATH_DATA_POLLING_CRON_EXPR)
                ->save();
        } catch (Exception $e) {
            throw new Exception(Mage::helper('cron')->__('Unable to save the cron expression.'));
        }
    }

}
