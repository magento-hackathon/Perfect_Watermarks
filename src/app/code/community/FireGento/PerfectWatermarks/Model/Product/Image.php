<?php

class FireGento_PerfectWatermarks_Model_Product_Image
    extends Mage_Catalog_Model_Product_Image
{
    private $_disableMemoryCheck = true;

    /**
     * @return bool
     */
    public function getDisableMemoryCheck()
    {
        return $this->_disableMemoryCheck;
    }

    /**
     * @param $disableMemoryCheck
     */
    public function setDisableMemoryCheck($disableMemoryCheck)
    {
        $this->_disableMemoryCheck = $disableMemoryCheck;
    }

    /**
     * Overwriten to choose dynamically the image processor.
     * @return Varien_Image
     */
    public function getImageProcessor()
    {
        if (!$this->_processor) {
            $this->_processor = new Varien_Image(
                $this->getBaseFile(),
                Mage::getStoreConfig('design/watermark_adapter/adapter')
            );
        }
        $this->_processor->keepAspectRatio($this->_keepAspectRatio);
        $this->_processor->keepFrame($this->_keepFrame);
        $this->_processor->keepTransparency($this->_keepTransparency);
        $this->_processor->constrainOnly($this->_constrainOnly);
        $this->_processor->backgroundColor($this->_backgroundColor);
        $this->_processor->quality($this->_quality);
        return $this->_processor;
    }

    /**
     * @param null $file
     * @return bool
     */
    protected function _checkMemory($file = null)
    {
        if ($this->getDisableMemoryCheck()) {
            return true;
        } else {
            return parent::_checkMemory($file = null);
        }
    }
}
