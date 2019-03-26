<?php
/**
 * Attribute Option Model
 *
 * @category Mageinn
 * @package  Mageinn_AdminExtra
 **/
class Mageinn_AdminExtra_Model_Attribute_Option extends Mage_Core_Model_Abstract
{
    private $_attributeId;
    private $_attributeObject;
    private $_storeOptionsValues;

    /**
     * Generate the export csv content
     *
     * @return string
     */
    public function generateExportCsv()
    {
        $values = $this->getOptionValues();
        $csvHeader = array('"option_id"');
        $csvFile = array();
        $stores = $this->getStores();
        foreach ($stores as $store) {
            $csvHeader[] = $this->_formatCsvValue($store->getCode());
        }
        $csvHeader[] = 'sort_order';
        $csvHeader[] = 'delete_option';

        $csvFile[] = implode(',', $csvHeader);

        foreach ($values as $value) {
            $csvLine = array($this->_formatCsvValue($value->getId()));

            foreach ($value['stores'] as $storeId => $storeValue) {
                $csvLine[] = $this->_formatCsvValue($storeValue);
            }
            $csvLine[] = $this->_formatCsvValue($value->getSortOrder());
            $csvLine[] = $this->_formatCsvValue(0);
            $csvFile[] = implode(',', $csvLine);
        }

        return implode("\n", $csvFile);
    }

    /**
     * Generate the export xml content
     *
     * @return string
     */
    public function generateExportXml()
    {
        $values = $this->getOptionValues();
        $stores = $this->getStores();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml.= '<items>';
        foreach ($values as $value) {
            $xml .= '<item>';
            $xml .= '<option_id>' . $value->getId() . '</option_id>';

            foreach ($stores as $store) {
                $xml .= ('<' . $store->getCode() . '>' . ((isset($value['stores'][$store->getId()])) ?  $value['stores'][$store->getId()] : '' ) . '</' . $store->getCode() . '>');
            }

            $xml .= '<sort_order>' . $value['sort_order'] . '</sort_order>';
            $xml .= '<delete_option>0</delete_option>';
            $xml .= '</item>';
        }
        $xml.= '</items>';

        return $xml;
    }

    /**
     * Getter for _attributeId
     *
     * @return mixed
     */
    public function getAttributeId()
    {
        return $this->_attributeId;
    }

    /**
     * Setter for _attributeId
     *
     * @param mixed $attributeId
     *
     * @return Iredeem_Catalog_Model_AttributeOption
     */
    public function setAttributeId($attributeId)
    {
        $this->_attributeId = $attributeId;
        return $this;
    }

    /**
     * Retrieve stores collection with default store
     *
     * @return Mage_Core_Model_Mysql4_Store_Collection
     */
    public function getStores()
    {
        $stores = Mage::getModel('core/store')
                ->getResourceCollection()
                ->setLoadDefault(true)
                ->load();

        return $stores;
    }

    /**
     * Retrieve attribute option values if attribute input type select or multiselect
     *
     * @return array
     */
    public function getOptionValues()
    {
        $attributeType = $this->getAttributeObject()->getFrontendInput();
        $defaultValues = $this->getAttributeObject()->getDefaultValue();
        if ($attributeType == 'select' || $attributeType == 'multiselect') {
            $defaultValues = explode(',', $defaultValues);
        } else {
            $defaultValues = array();
        }

        switch ($attributeType) {
            case 'select':
                $inputType = 'radio';
                break;
            case 'multiselect':
                $inputType = 'checkbox';
                break;
            default:
                $inputType = '';
                break;
        }

        $values = array();
        $optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($this->getAttributeObject()->getId())
            ->setPositionOrder('desc', true)
            ->load();

        foreach ($optionCollection as $option) {
            $value = array();
            if (in_array($option->getId(), $defaultValues)) {
                $value['checked'] = 'checked="checked"';
            } else {
                $value['checked'] = '';
            }

            $value['intype'] = $inputType;
            $value['id'] = $option->getId();
            $value['sort_order'] = $option->getSortOrder();
            $value['stores'] = array();

            foreach ($this->getStores() as $store) {
                $storeValues = $this->getStoreOptionValues($store->getId());
                if (isset($storeValues[$option->getId()])) {
                    $value['stores'][$store->getId()] = htmlspecialchars($storeValues[$option->getId()]);
                }
                else {
                    $value['stores'][$store->getId()] = '';
                }
            }
            $values[] = new Varien_Object($value);
        }


        return $values;
    }

    /**
     * Retrieve attribute option values for given store id
     *
     * @param integer $storeId
     * @return array
     */
    public function getStoreOptionValues($storeId)
    {
        $values = null;
        if (isset($this->_storeOptionsValues[$storeId])) {
            $values = $this->_storeOptionsValues[$storeId];
        }

        if (is_null($values)) {
            $values = array();
            $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setAttributeFilter($this->getAttributeObject()->getId())
                ->setStoreFilter($storeId, false)
                ->load();
            foreach ($valuesCollection as $item) {
                $values[$item->getId()] = $item->getValue();
            }
            $this->_storeOptionsValues[$storeId] = $values;
        }

        return $values;
    }

    /**
     * Load the attribute object
     *
     * @return mixed
     */
    public function getAttributeObject()
    {
        if ($this->_attributeObject) {
            return $this->_attributeObject;
        }

        if ($this->getAttributeId()) {
            $this->setAttributeObject(Mage::getModel('catalog/resource_eav_attribute')
                    ->load($this->getAttributeId()));
            return $this->_attributeObject;
        }

        return false;
    }

    /**
     * Setter for _attributeObject
     *
     * @param mixed $attributeObject
     * @return Iredeem_Catalog_Model_AttributeOption
     */
    public function setAttributeObject($attributeObject)
    {
        $this->_attributeObject = $attributeObject;

        return $this;
    }

    /**
     * Format the csv value
     *
     * @param $value
     *
     * @return string
     */
    protected function _formatCsvValue($value)
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    /**
     * Generate the options array for XML import
     *
     * @param $xmlValues
     *
     * @return array
     */
    public function generateOptionsFromXml($xmlValues)
    {
        $helper = Mage::helper('mageinn_adminextra');
        $options = array("value" => array(), "delete" => array());
        $optionNumber = 0;
        foreach ($xmlValues->getNode() as $key => $value) {
            $optionNumber++;
            $arrayValue = $value->asArray();
            $optionKey = (isset($arrayValue['option_id']) && $arrayValue['option_id']) ? $arrayValue['option_id'] : ("option_" . $optionNumber);
            $options['value'][$optionKey] = array();
            foreach ($this->getStores() as $store) {
                $isOptionDelete = isset($arrayValue['option_id']) && isset($arrayValue['delete_option']) && $arrayValue['delete_option'] == 1;
                if ($store->isAdmin() && (!isset($arrayValue[$store->getCode()]) || !$arrayValue[$store->getCode()]) && !$isOptionDelete ) {
                    Mage::throwException($helper->__("Invalid default value"));
                }

                if (isset($arrayValue[$store->getCode()])) {
                    $options['value'][$optionKey][$store->getId()] = $arrayValue[$store->getCode()];
                }
                if ($isOptionDelete) {
                    $options['delete'][$arrayValue['option_id']] = 1;
                }
                if (isset($arrayValue['option_id']) && isset($arrayValue['sort_order'])) {
                    $options['order'][$arrayValue['option_id']] = $arrayValue['sort_order'];
                }
            }
        }

        return $options;
    }

    /**
     * Generate the options array for CSV import
     *
     * @param $csvFile
     * @return array
     */
    public function generateOptionsFromCsv($csvFile)
    {
        $csvArray = $this->formatArray($this->getFileData($csvFile));

        $helper = Mage::helper('mageinn_adminextra');
        $options = array("value" => array(), "delete" => array());
        $optionNumber = 0;
        foreach ($csvArray as $key => $value) {
            $optionNumber++;
            $optionKey = (isset($value['option_id']) && $value['option_id']) ? $value['option_id'] : ("option_" . $optionNumber);
            $options['value'][$optionKey] = array();
            foreach ($this->getStores() as $store) {
                $isOptionDelete = isset($value['option_id']) && isset($value['delete_option']) && $value['delete_option'] == 1;
                if ($store->isAdmin() && (!isset($value[$store->getCode()]) || !$value[$store->getCode()]) && !$isOptionDelete ) {
                    Mage::throwException($helper->__("Invalid default value"));
                }
                if (isset($value[$store->getCode()])) {
                    $options['value'][$optionKey][$store->getId()] = $value[$store->getCode()];
                }
                if ($isOptionDelete) {
                    $options['delete'][$value['option_id']] = 1;
                }
                if (isset($value['option_id']) && isset($value['sort_order'])) {
                    $options['order'][$value['option_id']] = $value['sort_order'];
                }
            }
        }

        return $options;
    }

    /**
     * Retrieve data from file
     *
     * @param   string $file
     * @return  array
     */
    public function getFileData($file) 
    {
        $data = array();
        if (file_exists($file)) {
            $parser = new Varien_File_Csv();
            $data = $parser->getData($file);
        }
        return $data;
    }

    /**
     * Format array using header columns it will format it in a way so that keys
     * can be replaced with header columns instead of array default indexes
     *
     * @param   array $data
     * @return  array
     */
    public function formatArray($data)
    {
        $header = array();
        $csvData = array();
        $ctr = 0;
        foreach ($data as $rowData) {
            if ($ctr==0){
                $header = $rowData;
            }
            else{
                $csvData[$ctr-1] = array_combine($header, $rowData);
            }
            $ctr++;
        }
        return $csvData;
    }
}