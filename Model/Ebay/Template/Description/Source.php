<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Description;

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

    protected $emailTemplateFilter;
    protected $filterManager;

    //########################################

    function __construct(
        \Magento\Email\Model\Template\Filter $emailTemplateFilter,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
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
                $title = $this->getHelper('Module\Renderer\Description')
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

            $subTitle = $this->getHelper('Module\Renderer\Description')
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
        $src = $this->getEbayDescriptionTemplate()->getDescriptionSource();;

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
                $description = $this->getHelper('Module\Renderer\Description')
                    ->parseTemplate($src['template'], $this->getMagentoProduct());
                $this->addWatermarkForCustomDescription($description);
                break;

            default:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;
        }

        return str_replace(array('<![CDATA[', ']]>'), '', $description);
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
            $note = $this->getHelper('Module\Renderer\Description')->parseTemplate(
                $src['template'], $this->getMagentoProduct()
            );
        }

        return $note;
    }

    // ---------------------------------------

    public function getProductDetail($type)
    {
        if (!$this->getEbayDescriptionTemplate()->isProductDetailsModeAttribute($type)) {
            return NULL;
        }

        $attribute = $this->getEbayDescriptionTemplate()->getProductDetailAttribute($type);

        if (!$attribute) {
            return NULL;
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
            return array();
        }

        if (!$mainImage = $this->getMainImage()) {

            $defaultImageUrl = $this->getEbayDescriptionTemplate()->getDefaultImageUrl();
            if (empty($defaultImageUrl)) {
                return array();
            }

            $image = $this->modelFactory->getObject('Magento\Product\Image');
            $image->setUrl($defaultImageUrl);
            $image->setStoreId($this->getMagentoProduct()->getStoreId());

            return array($image);
        }

        if ($this->getEbayDescriptionTemplate()->isGalleryImagesModeNone()) {
            return array($mainImage);
        }

        $galleryImages = array();
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

                $image = $this->modelFactory->getObject('Magento\Product\Image');
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if (count($galleryImages) <= 0) {
            return array($mainImage);
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

            return array();
        }

        $variationImages = array();
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

                $image = $this->modelFactory->getObject('Magento\Product\Image');
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $variationImages)) {
                    continue;
                }

                $variationImages[$image->getHash()] = $image;
            }
        }

        if (count($variationImages) <= 0) {
            return array();
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

    private function imageLinkToPath($imageLink)
    {
        $imageLink = str_replace('%20', ' ', $imageLink);
        $imagePath = '';
        // TODO
//        $baseMediaUrl = Mage::app()->getStore($this->getMagentoProduct()->getStoreId())
//                                   ->getBaseUrl(Mage\Core\Model\Store::URL_TYPE_MEDIA, false).'catalog/product';
//
//        $imageLink = preg_replace('/^http(s)?:\/\//i', '', $imageLink);
//        $baseMediaUrl = preg_replace('/^http(s)?:\/\//i', '', $baseMediaUrl);
//
//        $baseMediaPath = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
//
//        $imagePath = str_replace($baseMediaUrl, $baseMediaPath, $imageLink);
//        $imagePath = str_replace('/', DS, $imagePath);
//        $imagePath = str_replace('\\', DS, $imagePath);

        return $imagePath;
    }

    private function pathToImageLink($path)
    {
        // TODO
//        $baseMediaUrl = Mage::app()->getStore($this->getMagentoProduct()->getStoreId())
//                                   ->getBaseUrl(Mage\Core\Model\Store::URL_TYPE_MEDIA, false).'catalog/product';
//
//        $baseMediaPath = Mage::getSingleton('catalog/product_media_config')->getBaseMediaPath();
//
//        $imageLink = str_replace($baseMediaPath, $baseMediaUrl, $path);
//        $imageLink = str_replace(DS, '/', $imageLink);

        $imageLink = '';
        return str_replace(' ', '%20', $imageLink);
    }

    // ---------------------------------------

    public function addWatermarkIfNeed($imageLink)
    {
        // TODO NOT SUPPORTED FEATURES "descripion policy watermark feature"

//        if (!$this->getEbayDescriptionTemplate()->isWatermarkEnabled()) {
//            return $imageLink;
//        }
//
//        $imagePath = $this->imageLinkToPath($imageLink);
//        if (!is_file($imagePath)) {
//            return $imageLink;
//        }
//
//        $fileExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
//        $pathWithoutExtension = preg_replace('/\.'.$fileExtension.'$/', '', $imagePath);
//
//        $markingImagePath = $pathWithoutExtension.'-'.$this->getEbayDescriptionTemplate()->getWatermarkHash()
//                            .'.'.$fileExtension;
//        if (is_file($markingImagePath)) {
//            $currentTime = $this->getHelper('Data')->getCurrentGmtDate(true);
//            if (filemtime($markingImagePath) +\Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_CACHE_TIME >
//                $currentTime) {
//                return $this->pathToImageLink($markingImagePath);
//            }
//
//            @unlink($markingImagePath);
//        }
//
//        $prevMarkingImagePath = $pathWithoutExtension.'-'
//                                .$this->getEbayDescriptionTemplate()->getWatermarkPreviousHash().'.'.$fileExtension;
//        if (is_file($prevMarkingImagePath)) {
//            @unlink($prevMarkingImagePath);
//        }
//
//        $varDir = new Ess\M2ePro\Model\VariablesDir(array(
//            'child_folder' => 'ebay/template/description/watermarks'
//        ));
//        $watermarkPath = $varDir->getPath().$this->getEbayDescriptionTemplate()->getId().'.png';
//        if (!is_file($watermarkPath)) {
//            $varDir->create();
//            @file_put_contents($watermarkPath, $this->getEbayDescriptionTemplate()->getWatermarkImage());
//        }
//
//        $watermarkPositions = array(
//           \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_TOP =>
//                                                                Varien\Image\Adapter\Abstract::POSITION_TOP_RIGHT,
//           \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_MIDDLE =>
//                                                                Varien\Image\Adapter\Abstract::POSITION_CENTER,
//           \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_BOTTOM =>
//                                                                Varien\Image\Adapter\Abstract::POSITION_BOTTOM_RIGHT
//        );
//
//        $image = new Varien\Image($imagePath);
//        $imageOriginalHeight = $image->getOriginalHeight();
//        $imageOriginalWidth = $image->getOriginalWidth();
//        $image->open();
//        $image->setWatermarkPosition(
//            $watermarkPositions[$this->getEbayDescriptionTemplate()->getWatermarkPosition()]
//        );
//
//        $watermark = new Varien\Image($watermarkPath);
//        $watermarkOriginalHeight = $watermark->getOriginalHeight();
//        $watermarkOriginalWidth = $watermark->getOriginalWidth();
//
//        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeStretch()) {
//            $image->setWatermarkPosition(Varien\Image\Adapter\AbstractModel::POSITION_STRETCH);
//        }
//
//        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeInWidth()) {
//            $watermarkWidth = $imageOriginalWidth;
//            $heightPercent = $watermarkOriginalWidth / $watermarkWidth;
//            $watermarkHeight = (int)($watermarkOriginalHeight / $heightPercent);
//
//            $image->setWatermarkWidth($watermarkWidth);
//            $image->setWatermarkHeigth($watermarkHeight);
//        }
//
//        if ($this->getEbayDescriptionTemplate()->isWatermarkScaleModeNone()) {
//            $image->setWatermarkWidth($watermarkOriginalWidth);
//            $image->setWatermarkHeigth($watermarkOriginalHeight);
//
//            if ($watermarkOriginalHeight > $imageOriginalHeight) {
//                $image->setWatermarkHeigth($imageOriginalHeight);
//                $widthPercent = $watermarkOriginalHeight / $imageOriginalHeight;
//                $watermarkWidth = (int)($watermarkOriginalWidth / $widthPercent);
//                $image->setWatermarkWidth($watermarkWidth);
//            }
//
//            if ($watermarkOriginalWidth > $imageOriginalWidth) {
//                $image->setWatermarkWidth($imageOriginalWidth);
//                $heightPercent = $watermarkOriginalWidth / $imageOriginalWidth;
//                $watermarkHeight = (int)($watermarkOriginalHeight / $heightPercent);
//                $image->setWatermarkHeigth($watermarkHeight);
//            }
//        }
//
//        $opacity = 100;
//        if ($this->getEbayDescriptionTemplate()->isWatermarkTransparentEnabled()) {
//            $opacity = 30;
//        }
//
//        $image->setWatermarkImageOpacity($opacity);
//        $image->watermark($watermarkPath);
//        $image->save($markingImagePath);
//
//        return $this->pathToImageLink($markingImagePath);
    }

    private function addWatermarkForCustomDescription(&$description)
    {
        // TODO NOT SUPPORTED FEATURES "descripion policy watermark feature"

//        if (strpos($description, 'm2e_watermark') !== false) {
//            preg_match_all('/<(img|a) [^>]*\bm2e_watermark[^>]*>/i', $description, $tagsArr);
//
//            $tags = $tagsArr[0];
//            $tagsNames = $tagsArr[1];
//
//            $count = count($tags);
//            for ($i = 0; $i < $count; $i++) {
//                $dom = new DOMDocument();
//                $dom->loadHTML($tags[$i]);
//                $tag = $dom->getElementsByTagName($tagsNames[$i])->item(0);
//
//                $newTag = str_replace(' m2e_watermark="1"', '', $tags[$i]);
//                if ($tagsNames[$i] === 'a') {
//                    $newTag = str_replace($tag->getAttribute('href'),
//                        $this->addWatermarkIfNeed($tag->getAttribute('href')), $newTag);
//                }
//                if ($tagsNames[$i] === 'img') {
//                    $newTag = str_replace($tag->getAttribute('src'),
//                        $this->addWatermarkIfNeed($tag->getAttribute('src')), $newTag);
//                }
//                $description = str_replace($tags[$i], $newTag, $description);
//            }
//        }
    }

    //########################################
}