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
 * AdminExtra Helper
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED                  = 'mageinn_adminextra/general/enabled';
    const XML_PATH_DUPLICATE_IMAGES_CLEAR   = 'mageinn_adminextra/general/duplicate_images_clear';
    const XML_PATH_DUPLICATE_URL_CLEAR      = 'mageinn_adminextra/general/duplicate_url_clear';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ENABLED);
    }

    /**
     * @return bool
     */
    public function clearImagesFlag()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DUPLICATE_IMAGES_CLEAR);
    }

    /**
     * @return bool
     */
    public function clearUrlFlag()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DUPLICATE_URL_CLEAR);
    }
}