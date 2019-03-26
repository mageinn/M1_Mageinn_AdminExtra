<?php
/**
 * Mageinn_AdminExtra_AttributeController
 *
 * @category   Mageinn
 * @package    Mageinn_AdminExtra
 * @author     Mageinn
 */
class Mageinn_AdminExtra_Adminhtml_ExtraController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Export options action
     */
    public function exportAction()
    {
        try {
            $type = $this->getRequest()->getParam('type');
            $attrId = $this->getRequest()->getParam('attribute_id');
            if (!$attrId) {
                Mage::throwException($this->__("Invalid Attribute ID"));
            }

            if ($attrId) {
                $attrOpt = Mage::getModel('mageinn_adminextra/attribute_option')
                        ->setAttributeId($attrId);

                switch ($type) {
                    case 'xml':
                        $content = $attrOpt->generateExportXml();
                        break;
                    case 'csv':
                        $content = $attrOpt->generateExportCsv();
                        break;
                    default:
                        Mage::throwException($this->__("Invalid File Type"));
                        break;
                }

                $this->getResponse ()->clearBody ();

                $this->getResponse()
                        ->setHttpResponseCode(200)
                        ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                        ->setHeader('Pragma', 'public', true )
                        ->setHeader('Content-Type', 'text/' . $this->getRequest()->getParam('type'))
                        ->setHeader('Content-Length', strlen($content) )
                        ->setHeader('Content-Disposition', 'attachment; filename="attr_' . $attrId . '_opts.' . $type . '"', true)
                        ->sendHeaders();
                $this->getResponse()->setBody($content);
                return;
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/*/');
    }

    /**
     * Import options action
     */
    public function importAction()
    {
        try {
            $attrId = $this->getRequest()->getPost('attribute_id');
            if (!$attrId) {
                Mage::throwException($this->__("Invalid attribute id"));
            }
            $attrOpt = Mage::getModel('mageinn_adminextra/attribute_option');

            $ext = substr($_FILES['imp_opt']['name'], strrpos($_FILES['imp_opt']['name'], '.')+1);
            if (isset($_FILES['imp_opt']['tmp_name'])) {
                switch ($ext) {
                    case 'xml':
                        $xmlElements = new Varien_Simplexml_Config($_FILES['imp_opt']['tmp_name']);
                        $attributeOptions = $attrOpt
                                ->generateOptionsFromXml($xmlElements);
                        break;
                    case 'csv':
                        $attributeOptions = $attrOpt
                            ->generateOptionsFromCsv($_FILES['imp_opt']['tmp_name']);
                        break;
                    default:
                        Mage::throwException($this->__("Invalid Import File Type. Only CSV or XML are allowed."));
                }

                $model = Mage::getModel('catalog/resource_eav_attribute')
                        ->load($attrId);
                if (!$model->getId()) {
                    Mage::throwException($this->__("Invalid Attribute ID"));
                }
                $model->setData("option", $attributeOptions)->save();
                $this->_getSession()->addSuccess($this->__('The file has been successfully imported.'));
            } else {
                Mage::throwException($this->__("Invalid Import File"));
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/catalog_product_attribute/edit', array('attribute_id' => $attrId));
    }

    public function massAssignAction()
    {
        $this->loadLayout()
            ->_addBreadcrumb(Mage::helper('mageinn_adminextra')->__('Manage Attributes'), Mage::helper('mageinn_adminextra')->__('Add to Attribute Sets'));
        $this->_addContent($this->getLayout()->createBlock('mageinn_adminextra/adminhtml_set_edit'));
        $this->renderLayout();
    }

    public function massRemoveAction()
    {
        $this->loadLayout()
            ->_addBreadcrumb(Mage::helper('mageinn_adminextra')->__('Manage Attributes'), Mage::helper('mageinn_adminextra')->__('Remove from Attribute Sets'));
        $this->_addContent($this->getLayout()->createBlock('mageinn_adminextra/adminhtml_set_edit'));
        $this->renderLayout();
    }

    /**
     *
     */
    public function massSaveAction()
    {
        $type = $this->getRequest()->getParam('type');
        $attributes = explode(",", $this->getRequest()->getParam('attribute'));
        $sets = $this->getRequest()->getParam('sets');
        $group = $this->getRequest()->getParam('group_name');

        $attrModel = Mage::getModel('catalog/product_attribute_set_api');
        if($type == Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_ASSIGN) {
            foreach($sets as $setId) {
                foreach($attributes as $attrId) {
                    if ($attrId) {
                        try {
                            $groupId = $this->_getGroupId($setId, $group);
                            if(!$groupId) {
                                $groupId = $attrModel->groupAdd($setId, $group);
                            }

                            $attrModel->attributeAdd($attrId, $setId, $groupId);
                        } catch (Mage_Api_Exception $e) {}
                    }
                }
            }
            $this->_getSession()->addSuccess($this->__('Selected attributes have been successfully assigned to your sets.'));
        } elseif($type == Mageinn_AdminExtra_Block_Adminhtml_Set_Edit_Form::TYPE_REMOVE) {
            foreach($sets as $setId) {
                foreach($attributes as $attrId) {
                    if ($attrId) {
                        try {
                            $attrModel->attributeRemove($attrId, $setId);
                        } catch (Mage_Api_Exception $e) {}
                    }
                }
            }
            $this->_getSession()->addSuccess($this->__('Selected attributes have been successfully removed from your sets.'));
        }
        $this->_redirect('adminhtml/catalog_product_attribute/');
    }

    /**
     * @param $setId
     * @param $group
     * @return int
     */
    protected function _getGroupId($setId, $group)
    {
        $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_group_collection')
            ->addFieldToFilter('attribute_set_id', $setId)
            ->load();

        foreach ($attributeSetCollection as $id => $attributeGroup) {
            if($attributeGroup->getAttributeGroupName() == $group) {
                return $attributeGroup->getAttributeGroupId();
            }
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/mageinn_adminextra');
    }
}