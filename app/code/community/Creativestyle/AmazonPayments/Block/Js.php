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
class Creativestyle_AmazonPayments_Block_Js extends Creativestyle_AmazonPayments_Block_Abstract {

    protected $_callbacks = null;
    protected $_scripts = null;

    protected function _addCallback($js, $callback) {
        if (null === $this->_callbacks || !is_array($this->_callbacks)) {
            $this->_callbacks = array($callback => array($js));
        } else {
            if (!array_key_exists($callback, $this->_callbacks)) {
                $this->_callbacks[$callback] = array();
            }
            $this->_callbacks[$callback][] = $js;
        }
        return $this;
    }

    protected function _addScript($js) {
        if (null === $this->_scripts || !is_array($this->_scripts)) {
            $this->_scripts = array($js);
        } else {
            $this->_scripts[] = $js;
        }
        return $this;
    }

    public function getWidgetUrl() {
        return $this->_getConfig()->getWidgetUrl();
    }

    public function getJsScripts() {
        if (null === $this->_callbacks && null === $this->_scripts) {
            foreach ($this->getSortedChildren() as $name) {
                $block = $this->getLayout()->getBlock($name);
                if (!$block) {
                    Mage::throwException(Mage::helper('core')->__('Invalid block: %s', $name));
                } else if ($block instanceof Creativestyle_AmazonPayments_Block_Js_Interface && $block->getCallbackName() && $block->toHtml()) {
                    $this->_addCallback($block->toHtml(), $block->getCallbackName());
                } else if ($block->toHtml()) {
                    $this->_addScript($block->toHtml());
                }
            }
        }

        $output = '';

        if (null !== $this->_callbacks && is_array($this->_callbacks)) {
            foreach ($this->_callbacks as $callbackName => $callbackFunctions) {
                if (is_array($callbackFunctions)) {
                    array_unshift($callbackFunctions, $callbackName . ' = function() {');
                    array_push($callbackFunctions, '}');
                    $output .= implode("\n", $callbackFunctions) . "\n\n";
                }
            }
        }

        if (null !== $this->_scripts && is_array($this->_scripts)) {
            foreach ($this->_scripts as $script) {
                $output .= $script . "\n\n";
            }
        }

        return $output;
    }

}
