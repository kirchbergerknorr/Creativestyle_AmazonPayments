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
class Creativestyle_AmazonPayments_Block_Adminhtml_Log_Ipn_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('amazonpayments_log_ipn_grid');
        $this->setFilterVisibility(false);
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('amazonpayments/log_collection')->setLogType('ipn');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn('timestamp', array(
            'header'        => Mage::helper('amazonpayments')->__('Date'),
            'index'         => 'timestamp',
            'type'          => 'datetime',
            'width'         => '150px',
            'renderer'      => 'Creativestyle_AmazonPayments_Block_Adminhtml_Renderer_Timestamp',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('notification_type', array(
            'header'        => Mage::helper('amazonpayments')->__('Notification type'),
            'index'         => 'notification_type',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('response_code', array(
            'header'        => Mage::helper('amazonpayments')->__('Response code'),
            'index'         => 'response_code',
            'align'         => 'center',
            'width'         => '80px',
            'filter'        => false,
            'sortable'      => false
        ));

        $this->addColumn('preview_action', array(
            'header'    => Mage::helper('amazonpayments')->__('Preview'),
            'type'      => 'action',
            'align'     => 'center',
            'width'     => '50px',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('amazonpayments')->__('Preview'),
                    'url'     => array('base' => '*/*/view'),
                    'field'   => 'id'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }

}
