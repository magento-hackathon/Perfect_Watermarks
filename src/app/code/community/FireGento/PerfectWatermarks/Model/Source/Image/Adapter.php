<?php
class FireGento_PerfectWatermarks_Model_Source_Image_Adapter
{
    /**
     * get Authtypes as Option Array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Varien_Image_Adapter::ADAPTER_GD2,
                'label' => Mage::helper('watermarks')->__('GD2 Adapter')
            ),
            array(
                'value' => Varien_Image_Adapter::ADAPTER_IM,
                'label' => Mage::helper('watermarks')->__('ImageMagick Adapter')
            )
        );
    }
}
