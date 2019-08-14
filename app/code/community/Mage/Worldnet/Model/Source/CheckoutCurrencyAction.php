<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Worldnet
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Worldnet Modes Resource
 *
 * @category   Mage
 * @package    Mage_Worldnet
 * @name       Mage_Worldnet_Model_Source_ModeAction
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Worldnet_Model_Source_checkoutCurrencyAction
{
    public function toOptionArray()
    {
        return array(
            array('value' => Mage_Worldnet_Model_Config::CHECKOUTCUR_STORE, 'label' => Mage::helper('worldnet')->__('Store base currency')),
            array('value' => Mage_Worldnet_Model_Config::CHECKOUTCUR_DISPLAY, 'label' => Mage::helper('worldnet')->__('Cart Display currency')),
        );
    }
}



