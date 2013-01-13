<?php
class FireGento_PerfectWatermarks_Test_Config_Main extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testCatalogProductImageRewriteConfigurationDefined()
    {
        return $this->assertModelAlias('catalog/product_image', 'FireGento_PerfectWatermarks_Model_Product_Image');
    }
}
