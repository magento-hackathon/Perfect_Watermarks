<?php

class FireGento_PerfectWatermarks_Test_Model_System_Config_Adapter
    extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var FireGento_PerfectWatermarks_Model_System_Config_Adapter
     */
    protected $_model = null;

    public function setUp()
    {
        $this->_model = Mage::getModel('watermarks/system_config_adapter');
    }

    public function testConfigurationNotSavedIfModuleNotInstalled()
    {
        $imagemagickMock = $this->getMock(
            'Varien_Image_Adapter_Imagemagic',
            array('checkDependencies')
        );

        $imagemagickMock
            ->expects($this->any())
            ->method('checkDependencies')
            ->will($this->throwException(new Exception()));

        $sessionMock = $this->getModelMock(
            'adminhtml/session',
            array(),
            false,
            array(),
            '',
            false
        );
        $sessionMock
            ->expects($this->once())
            ->method('addError')
            ->withAnyParameters();

        $this->replaceByMock('singleton', 'adminhtml/session', $sessionMock);

        $this->_model->setImageAdapter($imagemagickMock);
        $this->_model->setValue(Varien_Image_Adapter::ADAPTER_IM);

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_model, '_beforeSave'
        );

        $this->assertEquals(
            Varien_Image_Adapter::ADAPTER_GD2,
            $this->_model->getValue()
        );

    }

    public function testConfigurationSavedIfModuleNotInstalled()
    {
        $imagemagickMock = $this->getMock(
            'Varien_Image_Adapter_Imagemagic',
            array('checkDependencies')
        );

        $imagemagickMock
            ->expects($this->any())
            ->method('checkDependencies')
            ->will($this->returnValue(true));

        $this->_model->setImageAdapter($imagemagickMock);
        $this->_model->setValue(Varien_Image_Adapter::ADAPTER_GD2);

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $this->_model, '_beforeSave'
        );

        $this->assertEquals(
            Varien_Image_Adapter::ADAPTER_GD2,
            $this->_model->getValue()
        );
    }
}
