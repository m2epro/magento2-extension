<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

use Ess\M2ePro\Model\AbstractModel;
use \Magento\Framework\App\Area;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Image
 */
class Image extends AbstractModel
{
    protected $driverPool;
    protected $storeManager;
    protected $mediaConfig;
    protected $filesystem;

    protected $url = null;
    protected $path = null;

    protected $hash = null;

    protected $storeId = 0;
    protected $area = Area::AREA_FRONTEND;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->driverPool = $driverPool;
        $this->storeManager = $storeManager;
        $this->mediaConfig = $mediaConfig;
        $this->filesystem = $filesystem;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    //----------------------------------------

    /**
     * @return string
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = $this->getPathByUrl();
        }

        return $this->path;
    }

    /**
     * @param string|null $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    //----------------------------------------

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param string $area
     * @return $this
     */
    public function setArea($area)
    {
        $this->area = $area;
        return $this;
    }

    //----------------------------------------

    /**
     * @return string
     */
    public function getHash()
    {
        if ($this->hash) {
            return $this->hash;
        }

        return $this->hash = $this->generateHash($this->getUrl(), $this->getPath());
    }

    /**
     * @return $this
     */
    public function resetHash()
    {
        $this->hash = null;
        return $this;
    }

    private function generateHash($url, $path)
    {
        if ($this->isSelfHosted()) {
            return md5_file($path);
        }

        return sha1($url);
    }

    //----------------------------------------

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    //########################################

    public function isSelfHosted()
    {
        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        return $this->getPath() && $fileDriver->isFile($this->getPath());
    }

    //########################################

    public function getPathByUrl()
    {
        $imageUrl = str_replace('%20', ' ', $this->getUrl());
        $imageUrl = preg_replace('/^http(s)?:\/\//i', '', $imageUrl);

        $baseMediaUrl = $this->getBaseMediaUrl();
        $baseMediaUrl = preg_replace('/^http(s)?:\/\//i', '', $baseMediaUrl);

        $baseMediaPath = $this->filesystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath();

        $imagePath = str_replace($baseMediaUrl, $baseMediaPath, $imageUrl);
        $imagePath = str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
        $imagePath = str_replace('\\', DIRECTORY_SEPARATOR, $imagePath);

        return $imagePath;
    }

    public function getUrlByPath()
    {
        $baseMediaUrl  = $this->getBaseMediaUrl();
        $baseMediaPath = $this->filesystem
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)
            ->getAbsolutePath();

        $imageLink = str_replace($baseMediaPath, $baseMediaUrl, $this->getPath());
        $imageLink = str_replace(DIRECTORY_SEPARATOR, '/', $imageLink);

        return str_replace(' ', '%20', $imageLink);
    }

    //########################################

    private function getBaseMediaUrl()
    {
        $shouldBeSecure = $this->getArea() == Area::AREA_FRONTEND
            ? $this->getHelper('Component_Ebay_Images')->shouldBeUrlsSecure()
            : null;

        return $this->storeManager->getStore($this->storeId)->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA,
            $shouldBeSecure
        );
    }

    //########################################
}
