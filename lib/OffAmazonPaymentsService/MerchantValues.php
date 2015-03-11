<?php

/*******************************************************************************
 *  Copyright 2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *
 *  You may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at:
 *  http://aws.amazon.com/apache2.0
 *  This file is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 *  CONDITIONS OF ANY KIND, either express or implied. See the License
 *  for the
 *  specific language governing permissions and limitations under the
 *  License.
 * *****************************************************************************
 */



class OffAmazonPaymentsService_MerchantValues
{
    private $_merchantId;
    private $_accessKey;
    private $_secretKey;
    private $_serviceUrl;
    private $_widgetUrl;
    private $_applicationName;
    private $_applicationVersion;
    private $_region;
    private $_environment;
    private $_caBundleFile;
    private $_regionSpecificProperties;
    private $_clientId;
    
    public function __construct(
        $merchantId, 
        $accessKey, 
        $secretKey, 
        $applicationName, 
        $applicationVersion,
        $region,
        $environment,
        $serviceUrl,
        $widgetUrl,
        $caBundleFile,
    	$clientId
    ) {
        $this->_merchantId = $merchantId;
        $this->_accessKey = $accessKey;
        $this->_secretKey = $secretKey;
        $this->_applicationName = $applicationName;
        $this->_applicationVersion = $applicationVersion;
        $this->_region = strtoupper($region);
        $this->_environment = strtoupper($environment);
        $this->_caBundleFile = $caBundleFile;
        $this->_serviceUrl = $serviceUrl;
        $this->_widgetUrl = $widgetUrl;
        $this->_regionSpecificProperties = new OffAmazonPaymentsService_RegionSpecificProperties();
        $this->_clientId = $clientId;

        if ($this->_merchantId == "") {
            throw new InvalidArgumentException("merchantId not set in the properties file");
        }

        if ($this->_accessKey == "") {
            throw new InvalidArgumentException("accessKey not set in the properties file");
        }
        
        if ($this->_secretKey == "") {
            throw new InvalidArgumentException("secretKey not set in the properties file");
        }

        if ($this->_applicationName == "") {
            throw new InvalidArgumentException(
                "applicationName not set in the properties file"
            );
        }

        if ($this->_applicationVersion == "") {
            throw new InvalidArgumentException(
                "applicationVersion not set in the properties file"
            );
        }
        
        if ($this->_region == "") {
            throw new InvalidArgumentException("region not set in the properties file");
        } 
        $this->_region = $this->_validateRegion($this->_region);
        
        if ($this->_environment == "") {
            throw new InvalidArgumentException("environment not set in the properties file");
        }
        $this->_environment = $this->_validateEnvironment($this->_environment);

        if ($this->_caBundleFile == "") {
            $this->_caBundleFile = null;
        }
    }

    public function getMerchantId()
    {
        return $this->_merchantId;
    }

    public function getAccessKey()
    {
        return $this->_accessKey;
    }

    public function getSecretKey()
    {
        return $this->_secretKey;
    }

    public function getServiceUrl()
    {
        return $this->_regionSpecificProperties->getServiceUrlFor($this->_region, $this->_environment, $this->_serviceUrl);
    }
    
    public function getWidgetUrl()
    {
    	return $this->_regionSpecificProperties->getWidgetUrlFor($this->_region, $this->_environment, $this->_merchantId, $this->_widgetUrl);
    }
    
    public function getCurrency()
    {
    	return $this->_regionSpecificProperties->getCurrencyFor($this->_region);
    }
    
    public function getApplicationName()
    {
        return $this->_applicationName;
    }

    public function getApplicationVersion()
    {
        return $this->_applicationVersion;
    }
    
    public function getRegion()
    {
        return $this->_region;
    }
    
    public function getEnvironment()
    {
        return $this->_environment;
    }

    public function getCaBundleFile()
    {
        return $this->_caBundleFile;
    }
    
    public function getClientId()
    {
    	return $this->_clientId;
    }
    
    private function _validateRegion($region)
    {
        return self::_getValueForConstant($region, new OffAmazonPaymentsService_Regions());
    }
    
    private static function _validateEnvironment($environment)
    {
        return self::_getValueForConstant($environment, new OffAmazonPaymentsService_Environments());
    }
    
    private static function _getValueForConstant($constant, $valuesClass)
    {
        $rc = new ReflectionClass($valuesClass);
        $value = $rc->getConstant($constant);
        if ($value == null) {
            $allowedValues = implode(",", array_keys($rc->getConstants()));
            throw new InvalidArgumentException(
                "check your property file: " . $constant . " is not a valid option.  Available options are: " . $allowedValues
            );
        } 
        
        return $value;
    }
    
    public static function withRegionSpecificProperties(
    		$merchantId, 
    		$accessKey, 
    		$secretKey, 
    		$applicationName, 
    		$applicationVersion, 
    		$region, 
    		$environment, 
    		$serviceUrl, 
    		$widgetUrl,
    		$caBundleFile, 
    		$regionSpecificProperties,
			$clientId)
    {
    	$instance = new self($merchantId, $accessKey, $secretKey, $applicationName, $applicationVersion, $region, $environment, $serviceUrl, $widgetUrl, $caBundleFile, $clientId);
    	$instance->_regionSpecificProperties = $regionSpecificProperties;
    	return $instance;
    }
}

