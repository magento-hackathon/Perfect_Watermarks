<?php
/**
 *
 */
class Varien_Image_Adapter_Imagemagic extends Varien_Image_Adapter_Abstract
{

    protected $_requiredExtensions = array('imagick');

    protected $_allowedTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
    ];

    /**
     * Get the Imagemagick class.
     *
     * @return Imagick|null
     * @throws Exception
     */
    protected function getImageMagick()
    {
        if ($this->_imageHandler === null) {
            // Set tmp path since Imagick apparently does not choose it well (according to auditd file access errors)
            Imagick::setRegistry('temporary-path', Mage::getBaseDir('tmp'));
            $version = Imagick::getVersion();
            if (strpos($version['versionString'], 'ImageMagick 6.7.') === 0) {
                chdir(Mage::getBaseDir('tmp')); // Old versions don't use temporary-path but instead the cwd
            }
            $this->_imageHandler = new Imagick();
            if ($threadLimit = Mage::getStoreConfig('design/watermark_adapter/thread_limit')) {
                $this->_imageHandler->setResourceLimit(6,max(1,min((int)$threadLimit,24))); // No constant available for threads
            }
        }
        return $this->_imageHandler;
    }

    /**
     * Overrides broken core method (returns string the first time and int the second time)
     *
     * @return null|string
     * @throws Varien_Exception
     */
    public function getMimeType()
    {
        if( ! $this->_fileMimeType ) {
            list($this->_imageSrcWidth, $this->_imageSrcHeight, $this->_fileType, ) = @getimagesize($this->_fileName);
            if ( ! $this->_fileType) {
                throw new Varien_Exception('Could not get image file type.');
            }
            $this->_fileMimeType = image_type_to_mime_type($this->_fileType);
        }
        return $this->_fileMimeType;
    }

    /**
     * @param $fileName
     * @throws Varien_Exception
     */
    public function open($fileName)
    {
        Varien_Profiler::start(__METHOD__);
        $this->_fileName = $fileName;
        $this->_getFileAttributes();
        if ( ! in_array($this->getMimeType(), $this->_allowedTypes)) {
            throw new Varien_Exception('Unsupported image file type: '.$this->getMimeType());
        }
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
        Varien_Profiler::stop(__METHOD__);
    }

    /**
     * @param $angle
     */
    public function rotate($angle)
    {
        $this->getImageMagick()->rotateimage(new ImagickPixel(), $angle);
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
        /* because drlrdsen said so!  */
        $this->getImageMagick()->cropImage(
            $right - $left,
            $bottom - $top,
            $left,
            $top
        );
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
