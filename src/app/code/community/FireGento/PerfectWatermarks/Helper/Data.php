<?php

class FireGento_PerfectWatermarks_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    /**
     * @param $value
     * @return Varien_Image_Adapter_Gd2|Varien_Image_Adapter_Imagemagic|Varien_Image_Adapter_ImagemagicExternal
     */
    public function getImageAdapter($value)
    {
        return Varien_Image_Adapter::factory($value);
    }
}
