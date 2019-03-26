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
 * AdminExtra Observer
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Model_Observer
{
    protected $_parentGrid = null;

    protected $_controllers = array(
        'attribute', 
        'catalog_product_action_attribute'
        );
    
    /**
     * Detect attribute changes
     * 
     * @param Varien_Event_Observer $observer
     * @return \Mageinn_AdminExtra_Model_Observer
     */
    public function detectProductAttributeChanges(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('mageinn_adminextra')->isEnabled()) {
            return $this;
        }

        $attributesData = $observer->getEvent()->getAttributesData();
        $params = Mage::app()->getRequest()->getParams();

        if (isset($params['use_default'])) {
            $attrArray = $params['use_default'];
            $products = $observer->getEvent()->getProductIds();
            $stores = array($observer->getEvent()->getStoreId());

            $this->_deleteAttributesValue($attrArray, $products, $stores);

            foreach ($params['use_default'] as $attribute) {
                unset($attributesData[$attribute]);
            }
        }

        if (isset($params['copy_default'])) {
            $attrArray = $params['copy_default'];
            $products = $observer->getEvent()->getProductIds();
            $stores = array($observer->getEvent()->getStoreId());

            $this->_copyAttributesValue($attrArray, $products, $stores);

            foreach ($params['copy_default'] as $attribute) {
                unset($attributesData[$attribute]);
            }
        }

        $observer->getEvent()->setAttributesData($attributesData);
    }

    /**
     * Reset to default
     *
     * @param $attrArray
     * @param $products
     * @param $stores
     */
    protected function _deleteAttributesValue($attrArray, $products, $stores)
    {
        $productsAsString = implode(',', $products);
        $storesAsString = implode(',', $stores);

        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $eavConfig = Mage::getModel('eav/config');
        $tables = array();

        foreach ($attrArray as $attributeCode) {
            $attribute = $eavConfig->getAttribute('catalog_product', $attributeCode);
            if ($attribute) {
                $tableName = $resource->getTableName('catalog/product') . '_' . $attribute->getBackendType();
                $tables[$tableName][] = $attribute->getId();
            }
        }

        foreach ($tables as $tableName => $attributeIds) {
            $attributeIdsAsString = implode(',', $attributeIds);
            $q = "DELETE FROM {$tableName}
                WHERE
                    attribute_id IN ({$attributeIdsAsString}) AND
                    entity_id IN ({$productsAsString}) AND
                    store_id IN ({$storesAsString})";
            $connection->query($q);
        }
    }

    /**
     * Copy from default
     *
     * @param $attrArray
     * @param $products
     * @param $stores
     */
    protected function _copyAttributesValue($attrArray, $products, $stores)
    {
        $productsAsString = implode(',', $products);
        $storesAsString = implode(',', $stores);

        $resource = Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $eavConfig = Mage::getModel('eav/config');
        $tables = array();

        foreach ($attrArray as $attributeCode) {
            $attribute = $eavConfig->getAttribute('catalog_product', $attributeCode);
            if ($attribute) {
                $tableName = $resource->getTableName('catalog/product') . '_' . $attribute->getBackendType();
                $tables[$tableName][] = $attribute->getId();
            }
        }

        // Delete attribute values
        foreach ($tables as $tableName => $attributeIds) {
            $attributeIdsAsString = implode(',', $attributeIds);

            // Delete
            $q = "DELETE FROM {$tableName}
                WHERE
                    attribute_id IN ({$attributeIdsAsString}) AND
                    entity_id IN ({$productsAsString}) AND
                    store_id IN ({$storesAsString})";
            $connection->query($q);
        }

        // Copy attribute values
        $resource = Mage::getResourceModel('catalog/product');
        foreach ($tables as $tableName => $attributeIds) {
            // Loop through every attribute and every product
            foreach($attributeIds as $attrId) {
                foreach($products as $prodId) {
                    $value = $resource->getAttributeRawValue($prodId, $attrId, 0);
                    $value = $connection->quote($value);

                    if (!empty($value)) {
                        $q = "INSERT INTO {$tableName}
                        (entity_type_id, attribute_id, store_id, entity_id, value)
                        VALUES
                        (4,{$attrId},{$storesAsString},{$prodId},{$value})";

                        $connection->query($q);
                    }
                }
            }
        }
    }


    /**
     * Add "Import / Export Options" tab to attribute edit screen
     * 
     * @param Varien_Event_Observer $observer
     */
    public function layoutAfter(Varien_Event_Observer $observer) 
    {
        if (!Mage::helper('mageinn_adminextra')->isEnabled()) {
            return $this;
        }
        
        $block = $observer->getEvent()->getBlock();

        if($block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tabs) {
            $optionsExtraBlock = $block->getLayout()->createBlock(
                'adminhtml/widget',
                'options_import_export',
                array('template' => 'mageinn/adminextra/catalog/product/attribute/options.phtml')
            );
            
            $block->addTabAfter('options_import_export', array( 
                'label'     => Mage::helper('catalog')->__('Import / Export Options'),
                'title'     => Mage::helper('catalog')->__('Import / Export Options'),
                'content'   => $optionsExtraBlock->toHtml(),
            ),'labels');
        }
    }

    /**
     * Add Massaction for attributes grid
     *
     * @param Varien_Event_Observer $observer
     */
    public function layoutBefore(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('mageinn_adminextra')->isEnabled()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();

        /**
         * Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid
         */
        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid) {
            $block->setMassactionIdField('entity_id');
            $this->_parentGrid = $block;
        }

        if (null !== $this->_parentGrid && $block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction) {
            $block->setParentBlock($this->_parentGrid);
            $block->setFormFieldName('attribute');

            $block->addItem('assign', array(
                'label' => Mage::helper('mageinn_adminextra')->__('Add to Attribute Sets'),
                'url' => $block->getUrl('adminhtml/extra/massAssign', array('type' => Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_ASSIGN)),
                //'confirm' => Mage::helper('catalog')->__('Are you sure?')
            ));

            $block->addItem('remove', array(
                'label' => Mage::helper('mageinn_adminextra')->__('Remove from Attribute Sets'),
                'url' => $block->getUrl('adminhtml/extra/massRemove', array('type' => Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_REMOVE)),
                //'confirm' => Mage::helper('catalog')->__('Are you sure?')
            ));
        }
    }

    /**
     * HTML Before 
     * 
     * @param Varien_Event_Observer $observer
     * @return \Mageinn_AdminExtra_Model_Observer
     */
    public function htmlBefore(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('mageinn_adminextra')->isEnabled()) {
            return $this;
        }

        $block = $observer->getBlock();

        if (!isset($block)) {
            return $this;
        }

        $request = Mage::app()->getRequest();
        $storeId = $request->getParam('store');

        if ($storeId != 0) {
            if(in_array($request->getControllerName(), $this->_controllers) 
                    && $request->getActionName() == 'edit') {
                // Add use_default checkboxes
                if ($block instanceof Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element) {
                    $block->getDataObject()->setId('empty');
                    $block->getDataObject()->setStoreId($storeId);
                    $block->getDataObject()->setExistsStoreValueFlag($block->getAttribute()->getAttributeCode());

                    $afterElementHtml = $block->getElement()->getAfterElementHtml();
                    $afterElementHtml .= $this->_getAdditionalElementHtml($block->getElement());
                    $block->getElement()->setAfterElementHtml($afterElementHtml);
                }
                
                // Add Images Tab
                if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Action_Attribute_Tabs) {
                    $block->addTabAfter('images', array(
                        'label' => Mage::helper('mageinn_adminextra')->__('Images'),
                        'title' => Mage::helper('mageinn_adminextra')->__('Images'),
                        'content' => $block->getLayout()->createBlock('mageinn_adminextra/adminhtml_catalog_product_action_attribute_tab')->toHtml()
                    ), 'websites');
                }
            }
        }

        if(Mage::helper('mageinn_adminextra')->clearUrlFlag()) {
            if ($request->getControllerName() == 'catalog_product'
                && $request->getActionName() == 'edit'
                && $block instanceof Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element
                && $block->getElement()->getName() == 'product[url_key]'
            ) {
                $block->getElement()->setAfterElementHtml('<script>
            jQuery( document ).ready(function($) {
                $("#url_key").val(function(i, v) {
                    if(v === "mageinn-replace") {
                        $("#url_key_create_redirect").attr("checked", false);
                        $("#url_key_create_redirect").removeAttr("disabled");
                    }
                    return v.replace("mageinn-replace","");
                });
            });
            </script>');
            }
        }
    }

    /**
     * Fix attributes array
     * 
     * @param Varien_Event_Observer $observer
     * @return \Mageinn_AdminExtra_Model_Observer
     */
    public function preDispatch(Varien_Event_Observer $observer)
    {
        if (!Mage::helper('mageinn_adminextra')->isEnabled()) {
            return $this;
        }

        $request = Mage::app()->getRequest();

        if (in_array($request->getControllerName(), $this->_controllers) 
                && $request->getActionName() == 'save') {
            $params = $request->getParams();

            if(isset($params['use_default'])) {
                foreach ($params['use_default'] as $param) {
                    $params['attributes'][$param] = '';
                }

                $request->setParams($params);
            }

            if(isset($params['copy_default'])) {
                foreach ($params['copy_default'] as $param) {
                    $params['attributes'][$param] = '';
                }

                $request->setParams($params);
            }
        }
    }

    /**
     * Custom additional element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getAdditionalElementHtml($element)
    {
        return '<p class="attribute-change-checkbox" style="margin-top: 5px; margin-bottom: 15px;"><input type="checkbox" name="copy_default[]" id="' . $element->getId()
        . '-copy" value="' . $element->getEntityAttribute()->getAttributeCode() . '" /><label for="' . $element->getId() . '-copy">' . Mage::helper('catalog')->__('Copy <strong>%s</strong> from Default Store', $element->getEntityAttribute()->getFrontendLabel())
        . '</label><input type="hidden" disabled id="' . $element->getId() . '-copy-attr" name="attributes[' . $element->getEntityAttribute()->getAttributeCode() . ']" /></p>'
        . '<script>'
        . 'document.getElementById("' . $element->getId() . '-copy").onchange = function() {
             var elm = document.getElementById("' . $element->getId() . '-copy-attr");
             if(this.checked) {
                 // do something when checked
                 elm.value = "default";
                 elm.disabled = false;
             } else {
                 elm.value = "";
                 elm.disabled = true;
             }
        };'
        . '</script>';
    }

    /**
     * Duplicating fix for URL and Images
     *
     * @param Varien_Object $observer
     * @return Mage_Bundle_Model_Observer
     */
    public function duplicateProduct($observer)
    {
        $product = $observer->getEvent()->getCurrentProduct();
        $newProduct = $observer->getEvent()->getNewProduct();

        if(Mage::helper('mageinn_adminextra')->clearUrlFlag()) {
            $newProduct->setUrlKey('mageinn-replace');
        }

        if(Mage::helper('mageinn_adminextra')->clearImagesFlag()) {
            $newProduct->setMediaGallery(null);
        }

        return $this;
    }
}