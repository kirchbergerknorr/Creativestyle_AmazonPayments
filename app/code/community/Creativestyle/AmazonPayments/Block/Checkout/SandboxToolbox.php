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
class Creativestyle_AmazonPayments_Block_Checkout_SandboxToolbox extends Creativestyle_AmazonPayments_Block_Checkout_Abstract {

    public function getSimulationOptions() {
        return Mage::helper('core')->jsonEncode(Creativestyle_AmazonPayments_Model_Simulator::getAvailableSimulations());
    }

}
