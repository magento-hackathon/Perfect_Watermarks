<?php
class FireGento_PerfectWatermarks_Test_Config_Main
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testCatalogProductImageRewriteConfigurationDefined()
    {
        $this->assertModelAlias(
            'catalog/product_image',
            'FireGento_PerfectWatermarks_Model_Product_Image'
        );
    }

    public function testWatermarkModelConfigurationDefined()
    {
        $this->assertModelAlias(
            'watermarks/source_image_adapter',
            'FireGento_PerfectWatermarks_Model_Source_Image_Adapter'
        );
    }

    public function testWatermarkHelperConfigurationDefined()
    {
        $this->assertHelperAlias(
            'watermarks',
            'FireGento_PerfectWatermarks_Helper_Data'
        );
    }
}
