<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Item;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\Item\Importer
 */
class Importer extends \Ess\M2ePro\Model\AbstractModel
{
    private $fileDriver;

    private $filesystem;

    private $productMediaConfig;

    private $currencyFactory;

    /** @var $item \Ess\M2ePro\Model\Ebay\Order\Item */
    private $item = null;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Ess\M2ePro\Model\Ebay\Order\Item $item,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->fileDriver = $driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        $this->filesystem = $filesystem;
        $this->productMediaConfig = $productMediaConfig;
        $this->currencyFactory = $currencyFactory;
        $this->item = $item;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function getDataFromChannel()
    {
        $params = [];
        $params['item_id'] = $this->item->getItemId();

        $variationSku = $this->item->getVariationSku();
        if (!empty($variationSku)) {
            $params['variation_sku'] = $variationSku;
        }

        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'item',
            'get',
            'info',
            $params,
            'result',
            null,
            $this->item->getParentObject()->getOrder()->getAccount()
        );

        $dispatcherObj->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    //########################################

    /**
     * @param array $rawData
     * @return array
     */
    public function prepareDataForProductCreation(array $rawData)
    {
        $preparedData = [];

        $preparedData['title'] = trim(strip_tags($rawData['title']));
        $preparedData['short_description'] = trim($this->getHelper('Data')->stripInvisibleTags($rawData['title']));

        $description = isset($rawData['description']) ? $rawData['description'] : $preparedData['title'];
        $preparedData['description'] = $this->getHelper('Data')->stripInvisibleTags($description);

        if (!empty($rawData['sku'])) {
            $sku = $rawData['sku'];
        } else {
            $sku = $this->getHelper('Data')->convertStringToSku($rawData['title']);
        }

        if (strlen($sku) > \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH) {
            $hashLength = 10;
            $savedSkuLength = \Ess\M2ePro\Helper\Magento\Product::SKU_MAX_LENGTH - $hashLength - 1;
            $hash = $this->getHelper('Data')->generateUniqueHash($sku, $hashLength);

            $isSaveStart = (bool)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/order/magento/settings/',
                'save_start_of_long_sku_for_new_product'
            );

            if ($isSaveStart) {
                $sku = substr($sku, 0, $savedSkuLength).'-'.$hash;
            } else {
                $sku = $hash.'-'.substr($sku, strlen($sku) - $savedSkuLength, $savedSkuLength);
            }
        }

        $preparedData['sku'] = trim(strip_tags($sku));

        $preparedData['price'] = $this->getNewProductPrice($rawData);
        $preparedData['qty'] = $rawData['qty'] > 0 ? (int)$rawData['qty'] : 1;

        $preparedData['images'] = $this->getNewProductImages($rawData);

        return $preparedData;
    }

    /**
     * @param array $itemData
     * @return float
     */
    private function getNewProductPrice(array $itemData)
    {
        $currencyModel = $this->currencyFactory->create();
        $allowedCurrencies = $currencyModel->getConfigAllowCurrencies();
        $baseCurrencies = $currencyModel->getConfigBaseCurrencies();

        $isCurrencyAllowed = in_array($itemData['price_currency'], $allowedCurrencies);

        if ($isCurrencyAllowed && in_array($itemData['price_currency'], $baseCurrencies)) {
            return (float)$itemData['price'];
        }

        if (!$isCurrencyAllowed && !in_array($itemData['converted_price_currency'], $allowedCurrencies)) {
            return (float)$itemData['price'];
        }

        if (!$isCurrencyAllowed && in_array($itemData['converted_price_currency'], $baseCurrencies)) {
            return (float)$itemData['converted_price'];
        }

        $price = $isCurrencyAllowed ? $itemData['price'] : $itemData['converted_price_currency'];
        $currency = $isCurrencyAllowed ? $itemData['price_currency'] : $itemData['converted_price_currency'];

        $convertRate = $this->currencyFactory->create()->load($baseCurrencies[0])->getAnyRate($currency);
        $convertRate <= 0 && $convertRate = 1;

        return round($price / $convertRate, 2);
    }

    /**
     * @param array $itemData
     * @return array
     */
    private function getNewProductImages(array $itemData)
    {
        if (count($itemData['pictureUrl']) == 0) {
            return [];
        }

        try {
            $destinationFolder = $this->createDestinationFolder($itemData['title']);
        } catch (\Exception $e) {
            return [];
        }

        $images = [];
        $imageCounter = 1;

        $mediaPath = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        )->getAbsolutePath();

        foreach ($itemData['pictureUrl'] as $url) {
            preg_match('/\.(jpg|jpeg|png|gif)/', $url, $matches);

            $extension = isset($matches[0]) ? $matches[0] : '.jpg';
            $imagePath = $destinationFolder
                . DIRECTORY_SEPARATOR
                . $this->getHelper('Data')->convertStringToSku($itemData['title']);
            $imagePath .=  '-' . $imageCounter . $extension;

            try {
                $this->downloadImage($url, $imagePath);
            } catch (\Exception $e) {
                continue;
            }

            $images[] = str_replace($mediaPath.$this->productMediaConfig->getBaseTmpMediaPath(), '', $imagePath);
            $imageCounter++;
        }

        return $images;
    }

    private function createDestinationFolder($itemTitle)
    {
        $baseTmpImageName = $this->getHelper('Data')->convertStringToSku($itemTitle);

        $destinationFolder = $this->filesystem->getDirectoryRead(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        )->getAbsolutePath()
        . $this->productMediaConfig->getBaseTmpMediaPath() . DIRECTORY_SEPARATOR;

        $destinationFolder .= $baseTmpImageName{0} . DIRECTORY_SEPARATOR . $baseTmpImageName{1};

        if (!($this->fileDriver->isDirectory($destinationFolder)
            || $this->fileDriver->createDirectory($destinationFolder, 0777))) {
            // M2ePro\TRANSLATIONS
            // Unable to create directory '%directory%'.
            throw new \Ess\M2ePro\Model\Exception("Unable to create directory '{$destinationFolder}'.");
        }

        return $destinationFolder;
    }

    //########################################

    public function downloadImage($url, $imagePath)
    {
        $fileHandler = fopen($imagePath, 'w+');
        // ---------------------------------------

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, $url);

        curl_setopt($curlHandler, CURLOPT_FILE, $fileHandler);
        curl_setopt($curlHandler, CURLOPT_REFERER, $url);
        curl_setopt($curlHandler, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, 30);

        curl_exec($curlHandler);
        curl_close($curlHandler);

        fclose($fileHandler);
        // ---------------------------------------

        $imageInfo = $this->fileDriver->isFile($imagePath) ? getimagesize($imagePath) : null;

        if (empty($imageInfo)) {
            // M2ePro\TRANSLATIONS
            // Image %url% was not downloaded.
            throw new \Ess\M2ePro\Model\Exception("Image {$url} was not downloaded.");
        }
    }

    //########################################
}
