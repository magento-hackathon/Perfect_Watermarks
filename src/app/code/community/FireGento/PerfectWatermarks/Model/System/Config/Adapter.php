<?php
class FireGento_PerfectWatermarks_Model_System_Config_Adapter
    extends Mage_Core_Model_Config_Data
{
    /**
     * @var Varien_Image_Adapter_Abstract
     */
    protected $_imageAdapter = null;

    protected function _beforeSave()
    {
        try {
            $this->getImageAdapter()->checkDependencies();
        } catch (Exception $e) {
            $this->setValue(Varien_Image_Adapter::ADAPTER_GD2);
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('watermarks')
                    ->__('Selected library is not installed')
            );
        }
    }

    /**
     * @param \Varien_Image_Adapter_Abstract $imageAdapter
     */
    public function setImageAdapter($imageAdapter)
    {
        $this->_imageAdapter = $imageAdapter;
    }

    /**
     * @return \Varien_Image_Adapter_Abstract
     */
    public function getImageAdapter()
    {
        if ($this->_imageAdapter === null) {
            $this->_imageAdapter =
               Mage::helper('watermarks')->getImageAdapter($this->getValue());
        }
        return $this->_imageAdapter;
    }
}
