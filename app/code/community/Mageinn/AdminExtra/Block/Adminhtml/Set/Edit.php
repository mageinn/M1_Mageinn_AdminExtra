<?php
/**
 * Adminhtml Assign Block
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Block_Adminhtml_Set_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
               
        $this->_objectId = 'action_id';
        $this->_blockGroup = 'mageinn_adminextra';
        $this->_controller = 'adminhtml_set';

        $type = $this->getRequest()->getParam('type');
        if ($type == Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_ASSIGN) {
            $this->_updateButton('save', 'label', Mage::helper('mageinn_adminextra')->__('Assign'));
        } else {
            $this->_updateButton('save', 'label', Mage::helper('mageinn_adminextra')->__('Remove'));
        }

    }
 
    public function getHeaderText()
    {
        $type = $this->getRequest()->getParam('type');
        if ($type == Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_ASSIGN) {
            return Mage::helper('mageinn_adminextra')->__('Add to Attribute Sets');
        } else {
            return Mage::helper('mageinn_adminextra')->__('Remove from Attribute Sets');
        }

    }

    public function getBackUrl()
    {
        return $this->getUrl('adminhtml/catalog_product_attribute');
    }
}