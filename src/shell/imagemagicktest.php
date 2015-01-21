<?php
require_once 'abstract.php';

class Image_Batch_Performance extends Mage_Shell_Abstract
{

    /**
     * Run script
     *
     */
    public function run()
    {

        if (!$this->getArg('d')) {
            echo $this->usageHelp();
            return;
        }
        /** @var $iterator SplFileObject[] */
        $iterator = new DirectoryIterator($this->getArg('d'));
        $start = microtime(true);
        $i = 0;
        $max = 0;
        do {
            foreach ($iterator as $file) {
                if (!$file->isDir()) {
                    $image = new Varien_Image(
                        $this->getArg('d') . DS . $file->getFilename(),
                        Varien_Image_Adapter::ADAPTER_IM
                    );
                    $image->keepFrame(true);
                    $image->keepAspectRatio(true);
                    $image->keepTransparency(true);
                    $image->backgroundColor(array(255, 255, 255));
                    $image->resize(186, 500);
                    $image->setWatermarkImageOpacity(30);
                    $image->setWatermarkPosition(
                        Varien_Image_Adapter_Abstract::POSITION_TOP_LEFT
                    );
                    $image->setWatermarkHeigth(100);
                    $image->setWatermarkWidth(100);
                    $image->quality(80);
                    $watermark =
                        $this->getArg('d') . DS . 'watermark' . DS . 'watermark.png';
                    if (is_readable($watermark)) {
                        $image->watermark($watermark);
                    }
                    $image->save(
                        $this->getArg('d') . DS . 'result', $file->getFilename()
                    );
                }
            }
        } while ($i++ < $max);
        $endMem = memory_get_usage(true);
        $end = microtime(true);

        echo "Duration in seconds: " . (($end - $start)) . PHP_EOL;
        echo "Memory usage in MB: " . (($endMem / 1024) / 1024) . PHP_EOL;
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f imagemagicktest.php -- [options]

  -h            Short alias for help
  -d            directory with sample data
  help          This help

USAGE;
    }
}

$imageBatch = new Image_Batch_Performance();
$imageBatch->run();
