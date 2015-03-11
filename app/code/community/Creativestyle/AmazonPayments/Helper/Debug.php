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

/**
 * Amazon Payments debug data helper
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 */
class Creativestyle_AmazonPayments_Helper_Debug extends Mage_Core_Helper_Abstract {

    /**
     * Debug data array
     */
    protected $_debugData = null;

    /**
     * Reference timestamp
     */
    protected $_currentTimestamp = null;

    /**
     * Store views collection
     */
    protected $_storeCollection = null;

    protected $_configOutputCallbacks = array();

    /**
     * Constructor, sets necessary callbacks for config options output
     */
    public function __construct() {
        $this->_configOutputCallbacks = array(
            'amazonpayments/account/access_key' => array($this, '_validateAccessKey'),
            'amazonpayments/account/secret_key' => array($this, '_validateSecretKey'),
            'amazonpayments/general/active' => array($this, '_convertToBool'),
            'amazonpayments/general/sandbox' => array($this, '_convertToBool'),
            'amazonpayments/general/sandbox_toolbox' => array($this, '_convertToBool'),
            'amazonpayments/general/ipn_active' => array($this, '_convertToBool'),
            'amazonpayments/login/active' => array($this, '_convertToBool'),
            'amazonpayments/email/order_confirmation' => array($this, '_convertToBool'),
            'amazonpayments/design/responsive' => array($this, '_convertToBool'),
            'amazonpayments/developer/log_active' => array($this, '_convertToBool'),
        );
    }

    /**
     * Checks if for given config option callback function shall be used
     * On of the purposes of callbacks is to hide the confidential data
     */
    protected function _formatConfigValueOutput($key, $value) {
        if (array_key_exists($key, $this->_configOutputCallbacks)) {
            return call_user_func_array($this->_configOutputCallbacks[$key], array($value));
        }
        return $value;
    }

    /**
     * Gets and returns current timestamp for time-related data
     *
     * @return int
     */
    protected function _getCurrentTimestamp() {
        if (null === $this->_currentTimestamp) {
            $this->_currentTimestamp = Mage::getSingleton('core/date')->gmtTimestamp();
        }
        return $this->_currentTimestamp;
    }

    /**
     * Returns edition of Magento if available (Magento 1.7+)
     *
     * @return string
     */
    protected function _getMagentoEdition() {
        if (method_exists('Mage','getEdition')) {
            return Mage::getEdition() . ' Edition';
        }
        return '';
    }

    /**
     * Returns human readable period between two timestamps
     *
     * @param int|string $start
     * @param int|string $end
     * @return string
     */
    protected function _getElapsedTime($start, $end) {
        if (!($start && $end)) return 'No';
        $diff = $end - $start;
        $days = (int)($diff / 86400);
        $hours = (int)(($diff - $days * 86400)/ 3600);
        $minutes = (int)(($diff - $days * 86400 - $hours * 3600)/60);
        $seconds = (int)($diff - $days * 86400 - $hours * 3600 - $minutes * 60);
        $result = ($days ? $days . ' d ' : '') .
            ($hours ? $hours . ' h ' : '') .
            ($minutes ? $minutes . ' min. ' : '') .
            ($seconds ? $seconds . ' s ' : '');
        return trim($result);
    }

    /**
     * Returns all observers (class and its method) binded to the given event
     *
     * @param string $eventName
     * @return array
     */
    protected function _getEventObservers($eventName) {
        $observers = array();
        $areas = array(
            Mage_Core_Model_App_Area::AREA_GLOBAL,
            Mage_Core_Model_App_Area::AREA_FRONTEND,
            Mage_Core_Model_App_Area::AREA_ADMIN,
            Mage_Core_Model_App_Area::AREA_ADMINHTML
        );
        foreach ($areas as $area) {
            $eventConfig = Mage::getConfig()->getEventConfig($area, $eventName);
            if ($eventConfig) {
                foreach ($eventConfig->observers->children() as $obsName => $obsConfig) {
                    $class = Mage::getConfig()->getModelClassName($obsConfig->class ? (string)$obsConfig->class : $obsConfig->getClassName());
                    $method = (string)$obsConfig->method;
                    $args = implode(', ', (array)$obsConfig->args);
                    $observers[$area] = $class . '::' . $method . '(' . $args . ')';
                }
            }
        }
        return $observers;
    }

    /**
     * Returns store views collection
     *
     * @return Mage_Core_Model_Resource_Store_Collection
     */
    protected function _getStoreCollection() {
        if (null === $this->_storeCollection) {
            $this->_storeCollection = Mage::getModel('core/store')->getCollection()->load();
        }
        return $this->_storeCollection;
    }

    /**
     * Returns all store views array
     *
     * @return array
     */
    protected function _getStoreData() {
        $stores = array();
        $baseUrl = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL);
        $secureUrl = Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_SECURE_BASE_URL);
        $stores['__titles__'][0] = '<em>__default__</em>';
        $stores['Code'][0] = 'admin';
        $stores['Base URL'][0] = sprintf('<a href="%s">%s</a>', $baseUrl, $baseUrl);
        $stores['Secure URL'][0] = sprintf('<a href="%s">%s</a>', $secureUrl, $secureUrl);
        $stores['IPN URL'][0] = false;
        $stores['Locale'][0] = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE);
        $stores['Timezone'][0] = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
        $stores['Store ID'][0] = '0';
        $stores['Group ID'][0] = '0';
        $stores['Website ID'][0] = '0';
        foreach ($this->_getStoreCollection() as $store) {
            $baseUrl = Mage::getModel('core/url')->setStore($store->getId())->getUrl('/', array(
                '_current' => false, '_nosid' => true, '_store' => $store->getId()
            ));
            $secureUrl = Mage::getModel('core/url')->setStore($store->getId())->getUrl('/', array(
                '_current' => false, '_secure' => true, '_nosid' => true, '_store' => $store->getId()
            ));
            $ipnUrl = Mage::getModel('core/url')->setStore($store->getId())->getUrl('amazonpayments/advanced_ipn', array(
                '_current' => false, '_nosid' => true, '_store' => $store->getId()
            ));
            $stores['__titles__'][$store->getCode()] = $store->getName();
            $stores['Code'][$store->getCode()] = $store->getCode();
            $stores['Base URL'][$store->getCode()] = sprintf('<a href="%s">%s</a>', $baseUrl, $baseUrl);
            $stores['Secure URL'][$store->getCode()] = sprintf('<a href="%s">%s</a>', $secureUrl, $secureUrl);
            $stores['IPN URL'][$store->getCode()] = sprintf('<a href="%s">%s</a>', $ipnUrl, $ipnUrl);
            $stores['Locale'][$store->getCode()] = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $store->getId());
            $stores['Timezone'][$store->getCode()] = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $store->getId());
            $stores['Store ID'][$store->getCode()] = $store->getId();
            $stores['Group ID'][$store->getCode()] = $store->getGroupId();
            $stores['Website ID'][$store->getCode()] = $store->getWebsiteId();
        }
        return $stores;
    }

    /**
     * Returns general debug information
     *
     * @return array
     */
    protected function _getGeneralDebugData() {
        return array(
            'Pay with Amazon extension version' => (string)Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version'),
            'Pay with Amazon SDK library version' => OffAmazonPaymentsService_Client::APPLICATION_LIBRARY_VERSION,
            'Magento version' => trim(Mage::getVersion() . ' ' . $this->_getMagentoEdition()),
            'PHP version' => PHP_VERSION,
            'Magento Compiler enabled' => defined('COMPILER_INCLUDE_PATH'),
            'Current timestamp' => $this->_getCurrentTimestamp() . ' <em>&lt;' . Mage::getSingleton('core/date')->date("Y-m-d H:i:s", $this->_getCurrentTimestamp()) . '&gt;</em>'
        );
    }

    /**
     * Returns array of values of selected config options
     *
     * @param string $configPath
     * @return array
     */
    protected function _getConfigData($configPath) {
        $settings = array();
        $defaultScopeSettings = Mage::getStoreConfig($configPath);
        if ($defaultScopeSettings) {
            $keys = array();
            $settings['__titles__'][0] = '<em>__default__</em>';
            foreach ($defaultScopeSettings as $key => $value) {
                $keys[] = $key;
                $settings[$key][0] = $this->_formatConfigValueOutput($configPath . '/' . $key, $value);
            }
            foreach ($this->_getStoreCollection() as $store) {
                $settings['__titles__'][$store->getCode()] = $store->getName();
                foreach ($keys as $key) {
                    $settings[$key][$store->getCode()] = $this->_formatConfigValueOutput($configPath . '/' . $key, Mage::getStoreConfig($configPath . '/' . $key, $store->getId()));
                }
            }
        }
        return $settings;
    }

    /**
     * Returns Magento essential settings
     *
     * @return array
     */
    protected function _getMagentoGeneralData() {
        $settings = array();
        $settings['__titles__'][0] = '<em>__default__</em>';
        $settings['Allowed countries'][0] = str_replace(',', ', ', Mage::getStoreConfig('general/country/allow'));
        $settings['Optional postcode countries'][0] = str_replace(',', ', ', Mage::getStoreConfig('general/country/optional_zip_countries'));
        $settings['Base currency'][0] = Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE);
        $settings['Validate REMOTE_ADDR'][0] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_REMOTE_ADDR);
        $settings['Validate HTTP_VIA'][0] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_HTTP_VIA);
        $settings['Validate HTTP_X_FORWARDED_FOR'][0] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_X_FORWARDED);
        $settings['Validate HTTP_USER_AGENT'][0] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_USER_AGENT);
        $settings['Use SID on frontend'][0] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_FRONTEND_SID);
        $settings['Minimal order amount enabled'][0] = Mage::getStoreConfigFlag('sales/minimum_order/active');
        $settings['Minimal order amount'][0] = Mage::getStoreConfigFlag('sales/minimum_order/active') ? Mage::helper('core')->currency(Mage::getStoreConfig('sales/minimum_order/amount'), true, false) : false;
        foreach ($this->_getStoreCollection() as $store) {
            $settings['__titles__'][$store->getCode()] = $store->getName();
            $settings['Allowed countries'][$store->getCode()] = str_replace(',', ', ', Mage::getStoreConfig('general/country/allow', $store->getId()));
            $settings['Optional postcode countries'][$store->getCode()] = str_replace(',', ', ', Mage::getStoreConfig('general/country/optional_zip_countries', $store->getId()));
            $settings['Base currency'][$store->getCode()] = Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE, $store->getId());
            $settings['Validate REMOTE_ADDR'][$store->getCode()] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_REMOTE_ADDR, $store->getId());
            $settings['Validate HTTP_VIA'][$store->getCode()] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_HTTP_VIA, $store->getId());
            $settings['Validate HTTP_X_FORWARDED_FOR'][$store->getCode()] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_X_FORWARDED, $store->getId());
            $settings['Validate HTTP_USER_AGENT'][$store->getCode()] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_USER_AGENT, $store->getId());
            $settings['Use SID on frontend'][$store->getCode()] = Mage::getStoreConfigFlag(Mage_Core_Model_Session_Abstract::XML_PATH_USE_FRONTEND_SID, $store->getId());
            $settings['Minimal order amount enabled'][$store->getCode()] = Mage::getStoreConfigFlag('sales/minimum_order/active', $store->getId());
            $settings['Minimal order amount'][$store->getCode()] = Mage::getStoreConfigFlag('sales/minimum_order/active', $store->getId()) ? Mage::helper('core')->currency(Mage::getStoreConfig('sales/minimum_order/amount', $store->getId()), true, false) : false;
        }
        return $settings;
    }

    /**
     * Returns Amazon cronjobs status array
     *
     * @return array
     */
    protected function _getCronjobsData() {
        $cronjobs = array('__titles__' => array(
            'job_code' => 'Job code',
            'job_id' => 'Job ID',
            'status' => 'Status',
            'scheduled_at' => 'Scheduled at',
            'executed_at' => 'Executed at',
            'finished_at' => 'Finished at',
            'created_at' => 'Created at'
        ));
        $cronSchedule = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('job_code', array('amazonpayments_advanced_log_rotate', 'amazonpayments_advanced_data_poll'))
            ->setOrder('job_code', 'ASC')
            ->setOrder('scheduled_at', 'DESC')
            ->setOrder('executed_at', 'DESC')
            ->setOrder('finished_at', 'DESC')
            ->setOrder('created_at', 'DESC')
            ->load();
        $dateModel = Mage::getSingleton('core/date');
        foreach ($cronSchedule as $cron) {
            $cronjobs[$cron->getId()]['job_code'] = $cron->getJobCode();
            $cronjobs[$cron->getId()]['job_id'] = $cron->getId();
            $cronjobs[$cron->getId()]['status'] = $cron->getStatus();
            $cronjobs[$cron->getId()]['scheduled_at'] = ($cron->getScheduledAt() && $cron->getScheduledAt() != '0000-00-00 00:00:00') ? $dateModel->date("Y-m-d H:i:s", $cron->getScheduledAt()) : '';
            $cronjobs[$cron->getId()]['executed_at'] = ($cron->getExecutedAt() && $cron->getExecutedAt() != '0000-00-00 00:00:00') ? $dateModel->date("Y-m-d H:i:s", $cron->getExecutedAt()) : '';
            $cronjobs[$cron->getId()]['finished_at'] = ($cron->getFinishedAt() && $cron->getFinishedAt() != '0000-00-00 00:00:00') ? $dateModel->date("Y-m-d H:i:s", $cron->getFinishedAt()) : '';
            $cronjobs[$cron->getId()]['created_at'] = ($cron->getCreatedAt() && $cron->getCreatedAt() != '0000-00-00 00:00:00') ? $dateModel->date("Y-m-d H:i:s", $cron->getCreatedAt()) : '';
        }
        return $cronjobs;
    }

    /**
     * Returns observers binded to the essential events
     *
     * @return array
     */
    protected function _getEventsData() {
        $eventObservers = array();
        $events = Mage::getConfig()->getNode('global/creativestyle/amazonpayments/debug/events');
        $events = $events ? $events->asArray() : array();
        foreach ($events as $eventName => $eventData) {
            $eventObservers[$eventName] = $this->_getEventObservers($eventName);
        }
        return $eventObservers;
    }

    /**
     * Returns array of installed Magento extensions
     *
     * @return array
     */
    protected function _getMagentoExtensionsData() {
        $extensions = array();
        $modules = Mage::getConfig()->getNode('modules')->asArray();
        foreach ($modules as $key => $data) {
            $extensions[$key] = isset($data['active']) && ($data['active'] == 'false' || !$data['active']) ? false : (isset($data['version']) && $data['version'] ? $data['version'] : true);
        }
        return $extensions;
    }

    protected function _getPhpModulesData() {
        return array(
            'cURL' => function_exists('curl_init'),
            'PCRE' => function_exists('preg_replace'),
            'DOM' => class_exists('DOMNode'),
            'SimpleXML' => function_exists('simplexml_load_string')
        );
    }

    /**
     * Validates Amazon Access Key ID
     *
     * @param string $accessKey
     * @return bool
     */
    protected function _validateAccessKey($accessKey) {
        if (preg_match('/^[a-z0-9]{20}$/i', trim($accessKey))) return true;
        return false;
    }

    /**
     * Validates Amazon Secret Access Key
     *
     * @param string $secretKey
     * @return bool
     */
    protected function _validateSecretKey($secretKey) {
        if (preg_match('/\\s{1,}/', trim($secretKey))) return false;
        return true;
    }

    /**
     * Converts numeric string or integer value to boolean
     *
     * @param string|int $value
     * @return bool
     */
    protected function _convertToBool($value) {
        return (bool)$value;
    }

    /**
     * @param string|null $debugArea
     * @return array
     */
    public function getDebugData($debugArea = null) {
        if (null === $this->_debugData) {
            $this->_debugData = array(
                'type' => 'APA',
                'general' => $this->_getGeneralDebugData(),
                'stores' => $this->_getStoreData(),
                'amazon_account' => $this->_getConfigData('amazonpayments/account'),
                'amazon_general' => $this->_getConfigData('amazonpayments/general'),
                'amazon_login' => $this->_getConfigData('amazonpayments/login'),
                'amazon_email' => $this->_getConfigData('amazonpayments/email'),
                'amazon_design' => $this->_getConfigData('amazonpayments/design'),
                'amazon_design_login' => $this->_getConfigData('amazonpayments/design_login'),
                'amazon_design_pay' => $this->_getConfigData('amazonpayments/design_pay'),
                'amazon_developer' => $this->_getConfigData('amazonpayments/developer'),
                'magento_general' => $this->_getMagentoGeneralData(),
                'cronjobs' => $this->_getCronjobsData(),
                'event_observers' => $this->_getEventsData(),
                'magento_extensions' => $this->_getMagentoExtensionsData(),
                'php_modules' => $this->_getPhpModulesData()
            );
        }
        if (null === $debugArea) {
            return $this->_debugData;
        } else if (array_key_exists($debugArea, $this->_debugData)) {
            return $this->_debugData[$debugArea];
        }
        return array();
    }

}
