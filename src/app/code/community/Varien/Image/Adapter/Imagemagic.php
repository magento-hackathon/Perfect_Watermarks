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
        }
        return $this->_imageHandler;
    }

    /**
     * @param $fileName
     */
    public function open($fileName)
    {
        $this->_fileName = $fileName;
        $this->getMimeType();
        $this->_getFileAttributes();
        $this->getImageMagick()->readimage($fileName);
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
                throw
                new Exception(
                    "Unable to write into directory '{$destinationDir}'."
                );
            }
        }
        //set compression quality
        $this->getImageMagick()->setImageCompressionQuality($this->_quality);
        //remove all underlying information
        $this->getImageMagick()->stripImage();
        //write to file system
        $this->getImageMagick()->writeImage($fileName);
        //clear data and free resources
        $this->getImageMagick()->clear();
        $this->getImageMagick()->destroy();
    }

    public function display()
    {
        header("Content-type: " . $this->getMimeType());
        echo $this->getImageMagick();
    }

    public function resize($width = null, $height = null)
    {
        $widthFrame = $width;
        $heightFrame = $height;
        if ($width == null && $height == null) {
            return;
        }

        if ($height == null || $this->_keepAspectRatio == true) {
            $height = 0;
        }

        if ($width == null) {
            $width = 0;
        }

        $this->getImageMagick()->scaleImage($width, $height);
        //do only if we want a frame and the aspect ratio changed
        if ($this->_keepFrame
            && ($widthFrame != $this->getImageMagick()->getImageWidth()
                || $height != $this->getImageMagick()->getImageHeight())
        ) {
            $newFrameImage = new Imagick();
            $color = 'rgb(' . implode(',', $this->backgroundColor()) . ')';
            $newFrameImage->newImage(
                $widthFrame,
                $heightFrame,
                new ImagickPixel($color)
            );
            $imageHeight = $this->getImageMagick()->getImageHeight();
            $yPos = (($heightFrame - $imageHeight) / 2);
            $newFrameImage->compositeImage(
                $this->getImageMagick(),
                Imagick::COMPOSITE_OVER,
                0,
                $yPos
            );
            $this->getImageMagick()->clear();
            $this->getImageMagick()->destroy();
            $this->_imageHandler = $newFrameImage;
        }
    }

    public function rotate($angle)
    {
        $this->getImageMagick()->rotateimage(new ImagickPixel(), $angle);
    }

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

    public function watermark(
        $watermarkImage,
        $positionX = 0,
        $positionY = 0,
        $watermarkImageOpacity = 30,
        $repeat = false)
    {
        /** @var $watermark Imagick */
        $watermark = new Imagick($watermarkImage);

        if ($watermark->getImageAlphaChannel() == 0) {
            $watermarkImageOpacity =
                $this->getWatermarkImageOpacity() != null ?
                    $this->getWatermarkImageOpacity() : $watermarkImageOpacity;
            $watermark->setImageOpacity($watermarkImageOpacity / 100);
        }
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
    }

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
}
