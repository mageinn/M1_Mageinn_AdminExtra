<?php
/**
 * Adminhtml Assign Form Block
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    const TYPE_ASSIGN = 1;
    const TYPE_REMOVE = 2;

    protected function _prepareForm()
    {
        $type = $this->getRequest()->getParam('type');

        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('adminhtml/extra/massSave', array('type' => $type)),
                'method' => 'post'
            )
        );

        $fieldset = $form->addFieldset('merchant_form', array('legend' => Mage::helper('mageinn_adminextra')->__('Attribute Set Information')));

        $fieldset->addField('attribute', 'hidden',
        array(
            'name'      => 'attribute',
            'label'     => 'attribute',
            'class'     => 'required-entry',
            'required'  => true,
            'readonly'  => true,
            'value'     => implode(",",$this->getRequest()->getParam('attribute')),
            'after_element_html' => '<tr><td class="label"><label for="title">' . Mage::helper('mageinn_adminextra')->__('Total Attributes Selected') . '</label></td>
                <td class="value"><strong>' . sizeof($this->getRequest()->getParam('attribute')) . '</strong></td></tr>',
        ));


        if ($type == self::TYPE_ASSIGN) {
            $fieldset->addField('group_name', 'text', array(
                'label' => Mage::helper('mageinn_adminextra')->__('Group Name'),
                'class' => 'required-entry',
                'note' => Mage::helper('mageinn_adminextra')->__("The group will be created if it doesn't exist"),
                'required' => true,
                'name' => 'group_name'
            ));
        }

        $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
        $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter($entityType);
        $sets = array();
        foreach ($collection as $id => $set) {
            $sets[] = array('value' => $id, 'label' =>  $set->getAttributeSetName());
        }

        $fieldset->addField('sets', 'multiselect', array(
            'name' => 'sets[]',
            'label' => Mage::helper('mageinn_adminextra')->__('Attribute Sets'),
            'title' => Mage::helper('mageinn_adminextra')->__('Attribute Sets'),
            'required' => true,
            'values' => $sets
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}