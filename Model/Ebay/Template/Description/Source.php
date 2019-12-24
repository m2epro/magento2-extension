<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Description\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    const GALLERY_IMAGES_COUNT_MAX = 11;
    const VARIATION_IMAGES_COUNT_MAX = 12;

    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $descriptionTemplateModel \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    protected $driverPool;
    protected $gd2AdapterFactory;
    protected $imageFactory;
    protected $mediaConfig;
    protected $storeManager;
    protected $emailTemplateFilter;
    protected $filterManager;

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\Image\Adapter\Gd2Factory $gd2AdapterFactory,
        \Magento\Framework\ImageFactory $imageFactory,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Email\Model\Template\Filter $emailTemplateFilter,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->driverPool = $driverPool;
        $this->gd2AdapterFactory = $gd2AdapterFactory;
        $this->imageFactory = $imageFactory;
        $this->mediaConfig = $mediaConfig;
        $this->storeManager = $storeManager;
        $this->emailTemplateFilter = $emailTemplateFilter;
        $this->filterManager = $filterManager;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     * @return $this
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->descriptionTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     */
    public function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getTitle()
    {
        $title = '';
        $src = $this->getEbayDescriptionTemplate()->getTitleSource();

        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Ebay\Template\Description::TITLE_MODE_PRODUCT:
                $title = $this->getMagentoProduct()->getName();
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Description::TITLE_MODE_CUSTOM:
                $title = $this->getHelper('Module_Renderer_Description')
                    ->parseTemplate($src['template'], $this->getMagentoProduct());
                break;

            default:
                $title = $this->getMagentoProduct()->getName();
                break;
        }

        if ($this->getEbayDescriptionTemplate()->isCutLongTitles()) {
            $title = $this->cutLongTitles($title);
        }

        return $title;
    }

    /**
     * @return string
     */
    public function getSubTitle()
    {
        $subTitle = '';
        $src = $this->getEbayDescriptionTemplate()->getSubTitleSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Description::SUBTITLE_MODE_CUSTOM) {
            $subTitle = $this->getHelper('Module_Renderer_Description')
                ->parseTemplate($src['template'], $this->getMagentoProduct());

            if ($this->getEbayDescriptionTemplate()->isCutLongTitles()) {
                $subTitle = $this->cutLongTitles($subTitle, 55);
            }
        }

        return $subTitle;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getDescription()
    {
        $description = '';
        $src = $this->getEbayDescriptionTemplate()->getDescriptionSource();
        ;

        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_PRODUCT:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_SHORT:
                $description = $this->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_CUSTOM:
                $description = $this->getHelper('Module_Renderer_Description')
                    ->parseTemplate($src['template'], $this->getMagentoProduct());
                $this->addWatermarkForCustomDescription($description);
                break;

            default:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;
        }

        return str_replace(['<![CDATA[', ']]>'], '', $description);
    }

    //########################################

    /**
     * @return int|string
     */
    public function getCondition()
    {
        $src = $this->getEbayDescriptionTemplate()->getConditionSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_MODE_NONE) {
            return 0;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_MODE_ATTRIBUTE) {
            return $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $src['value'];
    }

    /**
     * @return string
     */
    public function getConditionNote()
    {
        $note = '';
        $src = $this->getEbayDescriptionTemplate()->getConditionNoteSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Ebay\Template\Description::CONDITION_NOTE_MODE_CUSTOM) {
            $note = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                $src['template'],
                $this->getMagentoProduct()
            );
        }

        return $note;
    }

    // ---------------------------------------

    public function getProductDetail($type)
    {
        if (!$this->getEbayDescriptionTemplate()->isProductDetailsModeAttribute($type)) {
            return null;
        }

        $attribute = $this->getEbayDescriptionTemplate()->getProductDetailAttribute($type);

        if (!$attribute) {
            return null;
        }

        return $this->getMagentoProduct()->getAttributeValue($attribute);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image|null
     */
    public function getMainImage()
    {
        $image = null;

        if ($this->getEbayDescriptionTemplate()->isImageMainModeProduct()) {
            $image = $this->getMagentoProduct()->getImage('image');
        }

        if ($this->getEbayDescriptionTemplate()->isImageMainModeAttribute()) {
            $src = $this->getEbayDescriptionTemplate()->getImageMainSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        if ($image) {
            $this->addWatermarkIfNeed($image);
        }

        return $image;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image[]
     */
    public function getGalleryImages()
    {
        if ($this->getEbayDescriptionTemplate()->isImageMainModeNone()) {
            return [];
        }

        if (!$mainImage = $this->getMainImage()) {
            $defaultImageUrl = $this->getEbayDescriptionTemplate()->getDefaultImageUrl();
            if (empty($defaultImageUrl)) {
                return [];
            }

            $image = $this->modelFactory->getObject('Magento_Product_Image');
            $image->setUrl($defaultImageUrl);
            $image->setStoreId($this->getMagentoProduct()->getStoreId());

            return [$image];
        }

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeNone()) {
            return [$mainImage];
        }

        $galleryImages = [];
        $gallerySource = $this->getEbayDescriptionTemplate()->getGalleryImagesSource();
        $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeProduct()) {
            $limitGalleryImages = (int)$gallerySource['limit'];
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages((int)$gallerySource['limit']+1);

            foreach ($galleryImagesTemp as $image) {
                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeAttribute()) {
            $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

            $galleryImagesTemp = $this->getMagentoProduct()->getAttributeValue($gallerySource['attribute']);
            $galleryImagesTemp = (array)explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                $image = $this->modelFactory->getObject('Magento_Product_Image');
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if (count($galleryImages) <= 0) {
            return [$mainImage];
        }

        foreach ($galleryImages as $key => $image) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Image $image */

            $this->addWatermarkIfNeed($image);

            if ($image->getHash() == $mainImage->getHash()) {
                unset($galleryImages[$key]);
            }
        }

        $galleryImages = array_slice($galleryImages, 0, $limitGalleryImages);
        array_unshift($galleryImages, $mainImage);

        return $galleryImages;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image[]
     */
    public function getVariationImages()
    {
        if ($this->getEbayDescriptionTemplate()->isImageMainModeNone() ||
            $this->getEbayDescriptionTemplate()->isVariationImagesModeNone()) {
            return [];
        }

        $variationImages = [];
        $variationSource = $this->getEbayDescriptionTemplate()->getVariationImagesSource();
        $limitVariationImages = self::VARIATION_IMAGES_COUNT_MAX;

        if ($this->getEbayDescriptionTemplate()->isVariationImagesModeProduct()) {
            $limitVariationImages = (int)$variationSource['limit'];
            $variationImagesTemp = $this->getMagentoProduct()->getGalleryImages((int)$variationSource['limit']);

            foreach ($variationImagesTemp as $image) {
                if (array_key_exists($image->getHash(), $variationImages)) {
                    continue;
                }

                $variationImages[$image->getHash()] = $image;
            }
        }

        if ($this->getEbayDescriptionTemplate()->isVariationImagesModeAttribute()) {
            $limitVariationImages = self::VARIATION_IMAGES_COUNT_MAX;

            $variationImagesTemp = $this->getMagentoProduct()->getAttributeValue($variationSource['attribute']);
            $variationImagesTemp = (array)explode(',', $variationImagesTemp);

            foreach ($variationImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                $image = $this->modelFactory->getObject('Magento_Product_Image');
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $variationImages)) {
                    continue;
                }

                $variationImages[$image->getHash()] = $image;
            }
        }

        if (count($variationImages) <= 0) {
            return [];
        }

        foreach ($variationImages as $image) {
            /** @var \Ess\M2ePro\Model\Magento\Product\Image $image */

            $this->addWatermarkIfNeed($image);
        }

        return array_slice($variationImages, 0, $limitVariationImages);
    }

    //########################################

    /**
     * @param string $str
     * @param int $length
     * @return string
     */
    private function cutLongTitles($str, $length = 80)
    {
        $str = trim($str);

        if ($str === '' || strlen($str) <= $length) {
            return $str;
        }

        return $this->filterManager->truncate($str, ['length' => $length]);
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Image $imageObj
     */
    public function addWatermarkIfNeed($imageObj)
    {
        if (!$this->getEbayDescriptionTemplate()->isWatermarkEnabled()) {
            return;
        }

        if (!$imageObj->isSelfHosted()) {
            return;
        }

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);

        $fileExtension = pathinfo($imageObj->getPath(), PATHINFO_EXTENSION);
        $pathWithoutExtension = preg_replace('/\.'.$fileExtension.'$/', '', $imageObj->getPath());

        $markingImagePath = $pathWithoutExtension.'-'.$this->getEbayDescriptionTemplate()->getWatermarkHash()
            .'.'.$fileExtension;

        if ($fileDriver->isFile($markingImagePath)) {
            $currentTime = $this->getHelper('Data')->getCurrentGmtDate(true);
            if (filemtime($markingImagePath) + \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_CACHE_TIME >
                $currentTime) {
                $imageObj->setPath($markingImagePath)
                    ->setUrl($imageObj->getUrlByPath())
                    ->resetHash();

                return;
            }

            $fileDriver->deleteFile($markingImagePath);
        }

        $prevMarkingImagePath = $pathWithoutExtension.'-'
            .$this->getEbayDescriptionTemplate()->getWatermarkPreviousHash().'.'.$fileExtension;

        if ($fileDriver->isFile($prevMarkingImagePath)) {
            $fileDriver->deleteFile($prevMarkingImagePath);
        }

        /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
        $varDir = $this->modelFactory->getObject('VariablesDir', ['data' => [
            'child_folder' => 'ebay/template/description/watermarks'
        ]]);
        $watermarkPath = $varDir->getPath().$this->getEbayDescriptionTemplate()->getId().'.png';
        if (!$fileDriver->isFile($watermarkPath)) {
            $varDir->create();
            file_put_contents($watermarkPath, $this->getEbayDescriptionTemplate()->getWatermarkImage());
        }

        $watermarkPositions = [
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_TOP =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_TOP_RIGHT,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_MIDDLE =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_CENTER,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_BOTTOM =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_BOTTOM_RIGHT
        ];

        /** @var \Magento\Framework\Image $image */
        $image = $this->imageFactory->create([
            'adapter' => $this->gd2AdapterFactory->create(),
            'fileName' => $imageObj->getPath()
        ]);
        $imageOriginalHeight = $image->getOriginalHeight();
        $imageOriginalWidth = $image->getOriginalWidth();
        $image->open();
        $image->setWatermarkPosition($watermarkPositions[$this->getEbayDescriptionTemplate()->getWatermarkPosition()]);

        /** @var \Magento\Framework\Image $watermark */
        $watermark = $this->imageFactory->create([
            'adapter' => $this->gd2AdapterFactory->create(),
            'fileName' => $watermarkPath
        ]);
        $watermarkOriginalHeight = $watermark->getOriginalHeight();
        $watermarkOriginalWidth = $watermark->getOriginalWidth();

        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeStretch()) {
            $image->setWatermarkPosition(\Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_STRETCH);
        }

        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeInWidth()) {
            $watermarkWidth = $imageOriginalWidth;
            $heightPercent = $watermarkOriginalWidth / $watermarkWidth;
            $watermarkHeight = (int)($watermarkOriginalHeight / $heightPercent);

            $image->setWatermarkWidth($watermarkWidth);
            $image->setWatermarkHeight($watermarkHeight);
        }

        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeNone()) {
            $image->setWatermarkWidth($watermarkOriginalWidth);
            $image->setWatermarkHeight($watermarkOriginalHeight);

            if ($watermarkOriginalHeight > $imageOriginalHeight) {
                $image->setWatermarkHeight($imageOriginalHeight);
                $widthPercent = $watermarkOriginalHeight / $imageOriginalHeight;
                $watermarkWidth = (int)($watermarkOriginalWidth / $widthPercent);
                $image->setWatermarkWidth($watermarkWidth);
            }

            if ($watermarkOriginalWidth > $imageOriginalWidth) {
                $image->setWatermarkWidth($imageOriginalWidth);
                $heightPercent = $watermarkOriginalWidth / $imageOriginalWidth;
                $watermarkHeight = (int)($watermarkOriginalHeight / $heightPercent);
                $image->setWatermarkHeight($watermarkHeight);
            }
        }

        $opacity = 100;
        if ($this->getEbayDescriptionTemplate()->isWatermarkTransparentEnabled()) {
            $opacity = 30;
        }

        $image->setWatermarkImageOpacity($opacity);
        $image->watermark($watermarkPath);
        $image->save($markingImagePath);

        if (!$fileDriver->isFile($markingImagePath)) {
            return;
        }

        $imageObj->setPath($markingImagePath)
            ->setUrl($imageObj->getUrlByPath())
            ->resetHash();
    }

    private function addWatermarkForCustomDescription(&$description)
    {
        if (strpos($description, 'm2e_watermark') !== false) {
            preg_match_all('/<(img|a) [^>]*\bm2e_watermark[^>]*>/i', $description, $tagsArr);

            $tags = $tagsArr[0];
            $tagsNames = $tagsArr[1];

            $count = count($tags);
            for ($i = 0; $i < $count; $i++) {
                $dom = new \DOMDocument();
                $dom->loadHTML($tags[$i]);
                $tag = $dom->getElementsByTagName($tagsNames[$i])->item(0);

                $newTag = str_replace(' m2e_watermark="1"', '', $tags[$i]);
                if ($tagsNames[$i] === 'a') {
                    $imageUrl = $tag->getAttribute('href');

                    /** @var \Ess\M2ePro\Model\Magento\Product\Image $image */
                    $image = $this->modelFactory->getObject('Magento_Product_Image');
                    $image->setUrl($imageUrl);
                    $image->setStoreId($this->getMagentoProduct()->getStoreId());
                    $this->addWatermarkIfNeed($image);

                    $newTag = str_replace($imageUrl, $image->getUrl(), $newTag);
                }
                if ($tagsNames[$i] === 'img') {
                    $imageUrl = $tag->getAttribute('src');

                    /** @var \Ess\M2ePro\Model\Magento\Product\Image $image */
                    $image = $this->modelFactory->getObject('Magento_Product_Image');
                    $image->setUrl($imageUrl);
                    $image->setStoreId($this->getMagentoProduct()->getStoreId());
                    $this->addWatermarkIfNeed($image);

                    $newTag = str_replace($imageUrl, $image->getUrl(), $newTag);
                }
                $description = str_replace($tags[$i], $newTag, $description);
            }
        }
    }

    //########################################
}
