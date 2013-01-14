<?php

class FireGento_PerfectWatermarks_Test_Config_System
    extends EcomDev_PHPUnit_Test_Case_Config
{
    public function testDefaultWatermarkImageAdapterPreset()
    {
        $this->assertDefaultConfigValue(
            'design/watermark/image_adapter',
            Varien_Image_Adapter::ADAPTER_GD2
        );
    }
}
