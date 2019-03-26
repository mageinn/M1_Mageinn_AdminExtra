<?php
/**
 * Mageinn_AdminExtra extension
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
 * @category    Mageinn
 * @package     Mageinn_AdminExtra
 * @copyright   Copyright (c) 2016 Mageinn. (http://mageinn.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * AdminExtra Attribute Helper
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Helper_Attribute extends Mage_Adminhtml_Helper_Catalog_Product_Edit_Action_Attribute
{
    /**
     * Return collection of same attributes for selected products without unique
     *
     * @return Mage_Eav_Model_Mysql4_Entity_Attribute_Collection
     */
    public function getAttributes()
    {
        $this->_excludedAttributes = array();
        return parent::getAttributes();
    }
}