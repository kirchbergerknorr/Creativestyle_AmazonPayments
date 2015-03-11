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
class Creativestyle_AmazonPayments_Model_Simulator {

    protected static $_availableSimulations = null;

    protected static function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    public static function getAvailableSimulations() {
        if (null === self::$_availableSimulations) {
            self::$_availableSimulations = array();
            $objects = self::_getConfig()->getGlobalDataValue('objects');
            if (is_array($objects) && !empty($objects)) {
                foreach ($objects as $objectKey => &$object) {
                    if (isset($object['states']) && is_array($object['states']) && !empty($object['states'])) {
                        foreach ($object['states'] as $stateKey => &$state) {
                            if (isset($state['reasons']) && is_array($state['reasons']) && !empty($state['reasons'])) {
                                foreach ($state['reasons'] as $reasonKey => &$reason) {
                                    if (isset($reason['simulation_allowed']) && $reason['simulation_allowed']) {
                                        unset($reason['simulation_allowed']);
                                    } else {
                                        unset($state['reasons'][$reasonKey]);
                                    }
                                }
                                if (empty($state['reasons'])) {
                                    unset($object['states'][$stateKey]);
                                }
                            } else if (isset($state['simulation_allowed']) && $state['simulation_allowed']) {
                                unset($state['simulation_allowed']);
                            } else {
                                unset($object['states'][$stateKey]);
                            }
                        }
                    }
                }
                self::$_availableSimulations = $objects;
            }
        }
        return self::$_availableSimulations;
    }

    public static function getSimulationOptions($object, $state, $reason = null) {
        $availableSimulations = self::getAvailableSimulations();
        if (isset($availableSimulations[$object])) {
            if (isset($availableSimulations[$object]['states'][$state])) {
                if (null === $reason) {
                    if (isset($availableSimulations[$object]['states'][$state]['simulation_options'])) {
                        return $availableSimulations[$object]['states'][$state]['simulation_options'];
                    }
                } else {
                    if (isset($availableSimulations[$object]['states'][$state]['reasons'][$reason]['simulation_options'])) {
                        return $availableSimulations[$object]['states'][$state]['reasons'][$reason]['simulation_options'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get simulation string for use in API calls
     *
     * @param Varien_Object $payment
     * @param string $transactionType
     * @return string
     */
    public static function simulate(Varien_Object $payment, $transactionType = null) {
        // object state simulations are available only in the sandbox mode
        if (null !== $transactionType && self::_getConfig()->isSandbox()) {
            $availableSimulations = self::getAvailableSimulations();
            // check if the requested transaction type is on the list of allowed simulations
            if (array_key_exists($transactionType, $availableSimulations)) {
                try {
                    $simulationData = $payment->getAdditionalInformation('_simulation_data');
                    // check if payment contains any simulation and for which transaction type
                    if (!empty($simulationData) && array_key_exists('object', $simulationData) && $simulationData['object'] == $transactionType) {
                        $simulation = array(
                            'SandboxSimulation' => array(
                                'State' => $simulationData['state']
                            )
                        );
                        if (array_key_exists('reason_code', $simulationData)) {
                            $simulation['SandboxSimulation']['ReasonCode'] = $simulationData['reason_code'];
                        }
                        if (array_key_exists('options', $simulationData) && is_array($simulationData['options'])) {
                            foreach ($simulationData['options'] as $option => $value) {
                                if (is_array($value) && isset($value['@']['type']) && isset($value[0])) {
                                    switch ($value['@']['type']) {
                                        case 'int':
                                            $simulation['SandboxSimulation'][$option] = (int)$value[0];
                                            break;
                                        default:
                                            $simulation['SandboxSimulation'][$option] = $value[0];
                                            break;
                                    }
                                } else {
                                    $simulation['SandboxSimulation'][$option] = $value;
                                }
                            }
                        }
                        $simulationString = Mage::helper('core')->jsonEncode($simulation);
                        if ($transactionType == 'OrderReference' && $simulationData['state'] == 'Closed') {
                            $amazonOrderReferenceId = $payment->getAdditionalInformation('amazon_order_reference_id');
                            Mage::getSingleton('amazonpayments/api_advanced')->closeOrderReference($amazonOrderReferenceId, $simulationString);
                        }
                        $payment->setAdditionalInformation('_simulation_data', null);
                        return $simulationString;
                    }
                } catch (Exception $e) {
                    Creativestyle_AmazonPayments_Model_Logger::logException($e);
                }
            }
        }
        return null;
    }

}
