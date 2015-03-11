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

$installer = $this;
$installer->startSetup();

$installer->addAttribute('customer', 'amazon_user_id', array(
    'type'      => 'varchar',
    'label'     => 'Amazon UID',
    'visible'   => false,
    'required'  => false,
    'unique'    => true
));

$installer->endSetup();