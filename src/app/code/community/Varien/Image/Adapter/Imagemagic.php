<?php
/**
 *
 */
class Varien_Image_Adapter_Imagemagic extends Varien_Image_Adapter_Abstract
{

    protected $_requiredExtensions = array('imagick');

    /**
     * Get the Imagemagick class.
     *
     * @return Imagick|null
     * @throws Exception
     */
    protected function getImageMagick()
    {
        if ($this->_imageHandler === null) {
            $this->_imageHandler = new Imagick();
            if ($threadLimit = Mage::getStoreConfig('design/watermark_adapter/thread_limit')) {
                $this->_imageHandler->setResourceLimit(6,max(1,min((int)$threadLimit,24))); // No constant available for threads
            }
        }
        return $this->_imageHandler;
    }

    /**
     * @param $fileName
     */
    public function open($fileName)
    {
        Varien_Profiler::start(__METHOD__);
        $this->_fileName = $fileName;
        $this->getMimeType();
        $this->_getFileAttributes();
        $this->getImageMagick()->readimage($fileName);
        Varien_Profiler::stop(__METHOD__);
    }

    /**
     * Write file to file system.
     *
     * @param null $destination
     * @param null $newName
     * @throws Exception
     */
    public function save($destination = null, $newName = null)
    {
        Varien_Profiler::start(__METHOD__);
        if (isset($destination) && isset($newName)) {
            $fileName = $destination . "/" . $newName;
        } elseif (isset($destination) && !isset($newName)) {
            $info = pathinfo($destination);
            $fileName = $destination;
            $destination = $info['dirname'];
        } elseif (!isset($destination) && isset($newName)) {
            $fileName = $this->_fileSrcPath . "/" . $newName;
        } else {
            $fileName = $this->_fileSrcPath . $this->_fileSrcName;
        }

        $destinationDir = (isset($destination)) ?
            $destination : $this->_fileSrcPath;

        if (!is_writable($destinationDir)) {
            try {
                $io = new Varien_Io_File();
                $io->mkdir($destination);
            } catch (Exception $e) {
                Varien_Profiler::stop(__METHOD__);
                throw
                new Exception(
                    "Unable to write into directory '{$destinationDir}'."
                );
            }
        }
        //set compression quality
        $this->getImageMagick()->setImageCompressionQuality(
            $this->getQuality()
        );
        //remove all underlying information
        $this->getImageMagick()->stripImage();
        //write to file system
        $this->getImageMagick()->writeImage($fileName);
        //clear data and free resources
        $this->getImageMagick()->clear();
        $this->getImageMagick()->destroy();
        Varien_Profiler::stop(__METHOD__);
    }

    /**
     * Just display the image
     */
    public function display()
    {
        header("Content-type: " . $this->getMimeType());
        echo $this->getImageMagick();
    }

    /**
     * @param null $frameWidth
     * @param null $frameHeight
     * @throws Exception
     */
    public function resize($frameWidth = null, $frameHeight = null)
    {
        if (empty($frameWidth) && empty($frameHeight)) {
            throw new Exception('Invalid image dimensions.');
        }

        Varien_Profiler::start(__METHOD__);
        $imagick = $this->getImageMagick();

        // calculate lacking dimension
        $origWidth = $imagick->getImageWidth();
        $origHeight = $imagick->getImageHeight();
        if ($this->keepFrame() === TRUE) {
            if (null === $frameWidth) {
                $frameWidth = $frameHeight;
            } elseif (null === $frameHeight) {
                $frameHeight = $frameWidth;
            }
        } else {
            if (null === $frameWidth) {
                $frameWidth = round($frameHeight * ($origWidth / $origHeight));
            } elseif (null === $frameHeight) {
                $frameHeight = round($frameWidth * ($origHeight / $origWidth));
            }
        }

        if ($this->_keepAspectRatio && $this->_constrainOnly) {
            if (($frameWidth >= $origWidth) && ($frameHeight >= $origHeight)) {
                $frameWidth = $origWidth;
                $frameHeight = $origHeight;
            }
        }

        // Resize
        $imagick->setimageinterpolatemethod(imagick::INTERPOLATE_BICUBIC);
        $imagick->scaleimage($frameWidth, $frameHeight, true);

        // Fill desired canvas
        if ($this->keepFrame() === TRUE
            && $frameWidth != $origWidth
            && $frameHeight != $origHeight
        ) {
            $composite = new Imagick();
            $color = $this->_backgroundColor;
            if ($color
                && is_array($color)
                && count($color) == 3
            ) {
                $bgColor = new ImagickPixel(
                    'rgb(' . implode(',', $color) . ')'
                );
            } else {
                $bgColor = new ImagickPixel('white');
            }
            $composite->newimage($frameWidth, $frameHeight, $bgColor);
            $composite->setimageformat($imagick->getimageformat());

            if($imagick->getimagecolorspace() == Imagick::COLORSPACE_CMYK) {
                $profiles = $imagick->getimageprofiles('*', false);
                // we're only interested if ICC profile(s) exist
                $has_icc_profile = (array_search('icc', $profiles) !== false);
                // if it doesnt have a CMYK ICC profile, we add one
                if ($has_icc_profile === false) {
                    $icc_cmyk = file_get_contents(__DIR__ . '/icc_profiles/USWebUncoated.icc');
                    $imagick->profileImage('icc', $icc_cmyk);
                    unset($icc_cmyk);
                }
                // then we add an RGB profile
                $icc_rgb = file_get_contents(__DIR__ . '/icc_profiles/sRGB.icc');
                $imagick->profileImage('icc', $icc_rgb);
                unset($icc_rgb);
                $imagick->setimagecolorspace(Imagick::COLORSPACE_SRGB);
            }

            $composite->setimagecolorspace($imagick->getimagecolorspace());
            $dstX = floor(($frameWidth - $imagick->getimagewidth()) / 2);
            $dstY = floor(($frameHeight - $imagick->getimageheight()) / 2);
            $composite->compositeimage(
                $imagick,
                Imagick::COMPOSITE_OVER,
                $dstX,
                $dstY
            );
            $this->_imageHandler = $composite;
            $imagick->clear();
            $imagick->destroy();
        }

        $this->refreshImageDimensions();

        Varien_Profiler::stop(__METHOD__);
    }

    /**
     * @param $angle
     */
    public function rotate($angle)
    {
        $this->getImageMagick()->rotateimage(new ImagickPixel(), $angle);

        $this->refreshImageDimensions();
    }

    /**
     * @param int $top
     * @param int $left
     * @param int $right
     * @param int $bottom
     */
    public function crop($top = 0, $left = 0, $right = 0, $bottom = 0)
    {
        if ($left == 0 && $top == 0 && $right == 0 && $bottom == 0) {
            return;
        }

        $newWidth = $this->_imageSrcWidth - $left - $right;
        $newHeight = $this->_imageSrcHeight - $top - $bottom;

        /* because drlrdsen said so!  */
        $this->getImageMagick()->cropImage(
            $newWidth,
            $newHeight,
            $left,
            $top
        );

        $this->refreshImageDimensions();
    }

    /**
     * @param $watermarkImage
     * @param int $positionX
     * @param int $positionY
     * @param int $watermarkImageOpacity
     * @param bool $repeat
     */
    public function watermark(
        $watermarkImage,
        $positionX = 0,
        $positionY = 0,
        $watermarkImageOpacity = 30,
        $repeat = false)
    {
        Varien_Profiler::start(__METHOD__);

        /** @var $watermark Imagick */
        $watermark = new Imagick($watermarkImage);

        //better method to blow up small images.
        $watermark->setimageinterpolatemethod(
            Imagick::INTERPOLATE_NEARESTNEIGHBOR
        );

        if ($this->_watermarkImageOpacity == null) {
            $opc = $watermarkImageOpacity;
        } else {
            $opc = $this->getWatermarkImageOpacity();
        }

        $watermark->evaluateImage(
            Imagick::EVALUATE_MULTIPLY,
            $opc,
            Imagick::CHANNEL_ALPHA
        );

        // how big are the images?
        $iWidth = $this->getImageMagick()->getImageWidth();
        $iHeight = $this->getImageMagick()->getImageHeight();

        //resize watermark to configuration size
        if ($this->getWatermarkWidth() &&
            $this->getWatermarkHeigth() &&
            ($this->getWatermarkPosition() != self::POSITION_STRETCH)
        ) {
            $watermark->scaleImage(
                $this->getWatermarkWidth(),
                $this->getWatermarkHeigth()
            );
        }

        // get watermark size
        $wWidth = $watermark->getImageWidth();
        $wHeight = $watermark->getImageHeight();

        //check if watermark is still bigger then image.
        if ($iHeight < $wHeight || $iWidth < $wWidth) {
            // resize the watermark
            $watermark->scaleImage($iWidth, $iHeight);
            // get new size
            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();
        }

        $x = 0;
        $y = 0;

        switch ($this->getWatermarkPosition()) {
            case self::POSITION_CENTER:
                $x = ($iWidth - $wWidth) / 2;
                $y = ($iHeight - $wHeight) / 2;
                break;
            case self::POSITION_STRETCH:
                $watermark->scaleimage($iWidth, $iHeight);
                break;
            case self::POSITION_TOP_RIGHT:
                $x = $iWidth - $wWidth;
                break;
            case self::POSITION_BOTTOM_LEFT:
                $y = $iHeight - $wHeight;
                break;
            case self::POSITION_BOTTOM_RIGHT:
                $x = $iWidth - $wWidth;
                $y = $iHeight - $wHeight;
                break;
            default:
                break;

        }

        $this->getImageMagick()->compositeImage(
            $watermark,
            Imagick::COMPOSITE_OVER,
            $x,
            $y
        );
        $watermark->clear();
        $watermark->destroy();
        Varien_Profiler::stop(__METHOD__);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function checkDependencies()
    {
        foreach ($this->_requiredExtensions as $value) {
            if (!extension_loaded($value)) {
                throw
                new Exception(
                    "Required PHP extension '{$value}' was not loaded."
                );
            }
        }
        return true;
    }

    protected function refreshImageDimensions()
    {
        $this->_imageSrcWidth = $this->_imageHandler->getImageWidth();
        $this->_imageSrcHeight = $this->_imageHandler->getImageHeight();
        $this->_imageHandler->setImagePage($this->_imageSrcWidth, $this->_imageSrcHeight, 0, 0);
    }

    public function __destruct()
    {
        @$this->getImageMagick()->clear();
        @$this->getImageMagick()->destroy();
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        if ($this->_quality == null) {
            $this->_quality = 80;
        }
        return $this->_quality;
    }

    /**
     * @return float
     */
    public function getWatermarkImageOpacity()
    {
        if ($this->_watermarkImageOpacity == 0) {
            return $this->_watermarkImageOpacity = 0;
        }
        return $this->_watermarkImageOpacity / 100;
    }
}
