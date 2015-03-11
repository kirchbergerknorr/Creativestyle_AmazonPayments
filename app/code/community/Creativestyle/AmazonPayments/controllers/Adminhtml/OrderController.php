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
class Creativestyle_AmazonPayments_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action {

    protected function _initAction() {
        $this->loadLayout()
            ->_setActiveMenu('amazonpayments/orders')
            ->_addBreadcrumb($this->__('Pay with Amazon'), $this->__('Pay with Amazon'))
            ->_addBreadcrumb($this->__('Orders'), $this->__('Orders'));
        return $this;
    }

    public function indexAction() {
        $this->_title($this->__('Pay with Amazon'))->_title($this->__('Orders'));
        $this->_initAction()
            ->renderLayout();
    }

    public function authorizeAction() {
        $orderId = $this->getRequest()->getParam('order_id', null);
        if (null !== $orderId) {
            try {
                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order->getId()) {
                    if (in_array($order->getPayment()->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                        $order->getPayment()->authorize(true, $order->getBaseTotalDue())->save();
                    }
                }
            } catch (OffAmazonPaymentsService_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Failed to authorize the payment.'));
                Mage::logException($e);
            }
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
            return;
        }
        $this->_redirect('*/*/index');
    }

}
