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
final class Creativestyle_AmazonPayments_Model_Logger {

    const AMAZON_LOG_DIR            = 'amazonpayments';

    const AMAZON_API_LOG_FILE       = 'apa_api.log';
    const AMAZON_EXCEPTION_LOG_FILE = 'apa_exception.log';
    const AMAZON_IPN_LOG_FILE       = 'apa_ipn.log';

    const LOGFILE_ROTATION_SIZE     = 8;

    /**
     * Returns config model instance
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    private static function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Returns path for selected logs and creates missing folder if needed
     *
     * @param string $logType
     * @return string|null
     */
    public static function getAbsoluteLogFilePath($logType) {
        try {
            $logDir = Mage::getBaseDir('log') . DS . self::AMAZON_LOG_DIR;
            if (!is_dir($logDir)) {
                mkdir($logDir, 0777, true);
            }
            switch ($logType) {
                case 'api':
                    return $logDir . DS . self::AMAZON_API_LOG_FILE;
                case 'exception':
                    return $logDir . DS . self::AMAZON_EXCEPTION_LOG_FILE;
                case 'ipn':
                    return $logDir . DS . self::AMAZON_IPN_LOG_FILE;
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return null;
    }

    /**
     * Logs a request made to Amazon Payments Advanced APIs
     *
     * @param array $callData
     */
    public static function logApiCall($callData) {
        if (self::_getConfig()->isLoggingActive()) {
            array_unshift($callData, Mage::getModel('core/date')->gmtTimestamp());
            if (($fileHandle = fopen(self::getAbsoluteLogFilePath('api'), 'a')) !== false) {
                fputcsv($fileHandle, $callData, self::_getConfig()->getLogDelimiter(), self::_getConfig()->getLogEnclosure());
                fclose($fileHandle);
            } else {
                Mage::log('PAY WITH AMAZON: unable to open ' . self::getAbsoluteLogFilePath('api') . ' for writing.');
            }
        }
    }

    /**
     * Logs incoming IPN request
     *
     * @param array $callData
     */
    public static function logIpnCall($callData) {
        if (self::_getConfig()->isLoggingActive()) {
            array_unshift($callData, Mage::getModel('core/date')->gmtTimestamp());
            $callData['message_xml'] = '';
            if (isset($callData['request_body']) && $callData['request_body']) {
                try {
                    $requestBody = Mage::helper('core')->jsonDecode($callData['request_body']);
                    if (isset($requestBody['Message'])) {
                        $requestBody['Message'] = Mage::helper('core')->jsonDecode($requestBody['Message']);
                        if (isset($requestBody['Message']['NotificationData'])) {
                            $callData['message_xml'] = $requestBody['Message']['NotificationData'];
                            $requestBody['Message']['NotificationData'] = 'see XML message';
                        }
                        $callData['request_body'] = Mage::helper('core')->jsonEncode($requestBody);
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
            if (($fileHandle = fopen(self::getAbsoluteLogFilePath('ipn'), 'a')) !== false) {
                fputcsv($fileHandle, $callData, self::_getConfig()->getLogDelimiter(), self::_getConfig()->getLogEnclosure());
                fclose($fileHandle);
            } else {
                Mage::log('PAY WITH AMAZON: unable to open ' . self::getAbsoluteLogFilePath('ipn') . ' for writing.');
            }
        }
    }

    /**
     * Logs an exception thrown during Amazon Payments processing or post-processing
     *
     * @param Exception $e
     */
    public static function logException(Exception $e) {
        if (self::_getConfig()->isLoggingActive()) {
            if (($fileHandle = fopen(self::getAbsoluteLogFilePath('exception'), 'a')) !== false) {
                $exceptionData = array(
                    'timestamp' => Mage::getModel('core/date')->gmtTimestamp(),
                    'exception_code' => $e->getCode(),
                    'exception_message' => $e->getMessage(),
                    'exception_trace' => $e->getTraceAsString()
                );
                fputcsv($fileHandle, $exceptionData, self::_getConfig()->getLogDelimiter(), self::_getConfig()->getLogEnclosure());
                fclose($fileHandle);
            } else {
                Mage::log('PAY WITH AMAZON: unable to open ' . self::getAbsoluteLogFilePath('exception') . ' for writing.');
            }
        }
    }

    public static function getColumnMapping($logType) {
        switch ($logType) {
            case 'api':
                return array('timestamp', 'call_url', 'call_action', 'query', 'response_code', 'response_error', 'response_headers', 'response_body');
            case 'exception':
                return array('timestamp', 'exception_code', 'exception_message', 'exception_trace');
            case 'ipn':
                return array('timestamp', 'notification_type', 'response_code', 'response_error', 'request_headers', 'request_body', 'message_xml');
        }
        return null;
    }

    public static function rotateLogfiles() {
        $logTypes = array('api', 'exception', 'ipn');
        $maxFilesize = self::LOGFILE_ROTATION_SIZE * 1048576;
        foreach ($logTypes as $logType) {
            $filepath = self::getAbsoluteLogFilePath($logType);
            if (file_exists($filepath) && filesize($filepath) > $maxFilesize) {
                rename($filepath, $filepath . '.' . Mage::getModel('core/date')->date("Ymdhis"));
            }
        }
    }

}
