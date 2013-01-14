<?php

class FireGento_PerfectWatermarks_Test_Model_Product_Image
    extends EcomDev_PHPUnit_Test_Case
{
    /** @var Mage_Catalog_Model_Product_Image */
    protected $_model;

    protected function setUp()
    {
        $this->_model = Mage::getModel('catalog/product_image');
    }

    public function testReturnsCorrectImageProcessorClass()
    {
        /** @var $image Varien_Image */
        $image = $this->_model->getImageProcessor();
        $adapterClass = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $image, '_getAdapter'
        );

        $this->assertInstanceOf('Varien_Image_Adapter_Abstract',
            $adapterClass);
    }
}
