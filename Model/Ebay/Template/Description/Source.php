<?php

namespace Ess\M2ePro\Model\Ebay\Template\Description;

use Ess\M2ePro\Model\Ebay\Template\Description as Description;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    public const GALLERY_IMAGES_COUNT_MAX = 23;
    public const VARIATION_IMAGES_COUNT_MAX = 12;

    private const CONDITIONAL_REPLACE_MAP = [
        Description::CONDITION_EBAY_GRADED => Description::CONDITION_EBAY_LIKE_NEW,
        Description::CONDITION_EBAY_UNGRADED => Description::CONDITION_EBAY_VERY_GOOD,
    ];

    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Model\Template\Description $descriptionTemplateModel */
    private $descriptionTemplateModel;

    protected $driverPool;
    protected $gd2AdapterFactory;
    protected $imageFactory;
    protected $mediaConfig;
    protected $storeManager;
    protected $emailTemplateFilter;
    protected $filterManager;

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

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct): self
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct(): ?\Ess\M2ePro\Model\Magento\Product
    {
        return $this->magentoProduct;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     *
     * @return $this
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance): self
    {
        $this->descriptionTemplateModel = $instance;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate(): ?\Ess\M2ePro\Model\Template\Description
    {
        return $this->descriptionTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getEbayDescriptionTemplate(): \Ess\M2ePro\Model\Ebay\Template\Description
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getTitle(): string
    {
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSubTitle(): string
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
     * @throws \Ess\M2ePro\Model\Exception|\Magento\Framework\Exception\FileSystemException
     */
    public function getDescription(): string
    {
        $src = $this->getEbayDescriptionTemplate()->getDescriptionSource();

        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_PRODUCT:
                $description = (string)$this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_SHORT:
                $description = (string)$this->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_CUSTOM:
                $description = $this->getHelper('Module_Renderer_Description')
                                    ->parseTemplate($src['template'], $this->getMagentoProduct());
                $this->addWatermarkForCustomDescription($description);
                break;

            default:
                $description = '';
                break;
        }

        return str_replace(['<![CDATA[', ']]>'], '', $description);
    }

    /**
     * @return int|string
     */
    public function getCondition()
    {
        $condition = $this->getConditionFromTemplate();

        return self::CONDITIONAL_REPLACE_MAP[(int)$condition] ?? $condition;
    }

    /**
     * @return int|string
     */
    private function getConditionFromTemplate()
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

    // ----------------------------------------

    /**
     * @return array{
     *     required_descriptors: array<int, int>,
     *     optional_descriptors: array<int, string>,
     *     not_found_attributes: string[]
     * }
     */
    public function getConditionDescriptors(): array
    {
        $descriptors = $this->getFilledConditionDescriptors();

        return [
            'required_descriptors' => $descriptors['required'],
            'optional_descriptors' => $descriptors['optional'],
            'not_found_attributes' => $descriptors['not_found'],
        ];
    }

    private function getFilledConditionDescriptors(): array
    {
        $defaultDescriptors = [
            'required' => [],
            'optional' => [],
            'not_found' => [],
        ];

        $condition = (int)$this->getConditionFromTemplate();

        if ($condition === Description::CONDITION_EBAY_GRADED) {
            return $this->getDescriptorsForGradedCondition($defaultDescriptors);
        }

        if ($condition === Description::CONDITION_EBAY_UNGRADED) {
            return $this->getDescriptorsForUngradedCondition($defaultDescriptors);
        }

        return $defaultDescriptors;
    }

    private function getDescriptorsForGradedCondition(array $descriptors): array
    {
        $template = $this->getEbayDescriptionTemplate();

        $professionalGraderId = $this->retrieveConditionDescriptorProfessionalGraderId($template);
        $gradeId = $this->retrieveConditionDescriptorGradeId($template);

        // ----------------------------------------

        $notFound = [];
        if ($professionalGraderId === null) {
            $notFound[] = 'Professional Grader';
        }

        if ($gradeId === null) {
            $notFound[] = 'Grade';
        }

        if (!empty($notFound)) {
            $descriptors['not_found'] = $notFound;

            return $descriptors;
        }

        // ----------------------------------------

        $descriptors['required'][Description::CONDITION_DESCRIPTOR_ID_PROFESSIONAL_GRADER] = $professionalGraderId;
        $descriptors['required'][Description::CONDITION_DESCRIPTOR_ID_GRADE] = $gradeId;

        if ($certNumber = $this->retrieveConditionDescriptorCertificationNumber($template)) {
            $descriptors['optional'][Description::CONDITION_DESCRIPTOR_ID_CERTIFICATION_NUMBER] = $certNumber;
        }

        return $descriptors;
    }

    private function getDescriptorsForUngradedCondition(array $descriptors): array
    {
        $template = $this->getEbayDescriptionTemplate();
        $cardConditionId = $this->retrieveConditionGradeCardConditionId($template);

        if ($cardConditionId === null) {
            $descriptors['not_found'][] = 'Card Condition';

            return $descriptors;
        }

        $descriptors['required'][Description::CONDITION_DESCRIPTOR_ID_CARD_CONDITION] = $cardConditionId;

        return $descriptors;
    }

    private function retrieveConditionDescriptorProfessionalGraderId(Description $template): ?string
    {
        if ($template->isConditionProfessionalGraderIdModeEbay()) {
            return $template->getConditionProfessionalGraderIdValue();
        }

        if ($template->isConditionProfessionalGraderIdModeAttribute()) {
            $attribute = $this->findProductAttributeValue(
                $template->getConditionProfessionalGraderIdAttribute()
            );

            $flippedMap = array_flip(
                Description::getConditionalProfessionalGraderIdLabelMap()
            );

            return $flippedMap[$attribute] ?? null;
        }

        return null;
    }

    private function retrieveConditionDescriptorGradeId(Description $template): ?string
    {
        if ($template->isConditionGradeIdModeEbay()) {
            return $template->getConditionGradeIdValue();
        }

        if ($template->isConditionGradeIdModeAttribute()) {
            $attribute = $this->findProductAttributeValue(
                $template->getConditionGradeIdAttribute()
            );

            $flippedMap = array_flip(
                Description::getConditionalGradeIdLabelMap()
            );

            return $flippedMap[$attribute] ?? null;
        }

        return null;
    }

    private function retrieveConditionDescriptorCertificationNumber(Description $template): ?string
    {
        if ($template->isConditionGradeCertificationModeCustom()) {
            return $template->getConditionGradeCertificationCustomValue();
        }

        if ($template->isConditionGradeCertificationModeAttribute()) {
            return $this->findProductAttributeValue(
                $template->getConditionGradeCertificationAttribute()
            );
        }

        return null;
    }

    private function retrieveConditionGradeCardConditionId(Description $template): ?string
    {
        if ($template->isConditionGradeCardConditionEbay()) {
            return $template->getConditionGradeCardConditionIdValue();
        }

        if ($template->isConditionGradeCardConditionModeAttribute()) {
            $attribute = $this->findProductAttributeValue(
                $template->getConditionGradeCardConditionIdAttribute()
            );

            $flippedMap = array_flip(
                Description::getConditionalCardConditionIdLabelMap()
            );

            return $flippedMap[$attribute] ?? null;
        }

        return null;
    }

    // ----------------------------------------

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getConditionNote(): string
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

    /**
     * @param $type
     *
     * @return string|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductDetail($type): ?string
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

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image|null
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Magento\Framework\Exception\FileSystemException
     */
    public function getMainImage(): ?\Ess\M2ePro\Model\Magento\Product\Image
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
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Magento\Framework\Exception\FileSystemException
     */
    public function getGalleryImages(): array
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
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages((int)$gallerySource['limit'] + 1);

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
     * @throws \Ess\M2ePro\Model\Exception\Logic|\Magento\Framework\Exception\FileSystemException
     */
    public function getVariationImages(): array
    {
        if (
            $this->getEbayDescriptionTemplate()->isImageMainModeNone() ||
            $this->getEbayDescriptionTemplate()->isVariationImagesModeNone()
        ) {
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

    /**
     * @param string $str
     * @param int $length
     *
     * @return string
     */
    private function cutLongTitles(string $str, int $length = 80): string
    {
        $str = trim($str);

        if ($str === '' || strlen($str) <= $length) {
            return $str;
        }

        return $this->filterManager->truncate($str, ['length' => $length]);
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Image $imageObj
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function addWatermarkIfNeed($imageObj): void
    {
        if (!$this->getEbayDescriptionTemplate()->isWatermarkEnabled()) {
            return;
        }

        if (!$imageObj->isSelfHosted()) {
            return;
        }

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);

        $fileExtension = pathinfo($imageObj->getPath(), PATHINFO_EXTENSION);
        $pathWithoutExtension = preg_replace('/\.' . $fileExtension . '$/', '', $imageObj->getPath());

        $markingImagePath = $pathWithoutExtension . '-' . $this->getEbayDescriptionTemplate()->getWatermarkHash()
            . '.' . $fileExtension;

        if ($fileDriver->isFile($markingImagePath)) {
            $currentTime = $this->getHelper('Data')->getCurrentGmtDate(true);
            // @codingStandardsIgnoreLine
            if (
                filemtime($markingImagePath) + \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_CACHE_TIME >
                $currentTime
            ) {
                $imageObj->setPath($markingImagePath)
                         ->setUrl($imageObj->getUrlByPath())
                         ->resetHash();

                $imageObj->markAsHasWatermark();

                return;
            }

            $fileDriver->deleteFile($markingImagePath);
        }

        $prevMarkingImagePath = $pathWithoutExtension . '-'
            . $this->getEbayDescriptionTemplate()->getWatermarkPreviousHash() . '.' . $fileExtension;

        if ($fileDriver->isFile($prevMarkingImagePath)) {
            $fileDriver->deleteFile($prevMarkingImagePath);
        }

        /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
        $varDir = $this->modelFactory->getObject('VariablesDir', [
            'data' => [
                'child_folder' => 'ebay/template/description/watermarks',
            ],
        ]);
        $watermarkPath = $varDir->getPath() . $this->getEbayDescriptionTemplate()->getId() . '.png';
        if (!$fileDriver->isFile($watermarkPath)) {
            $varDir->create();
            // @codingStandardsIgnoreLine
            file_put_contents($watermarkPath, base64_decode($this->getEbayDescriptionTemplate()->getWatermarkImage()));
        }

        $watermarkPositions = [
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_TOP_RIGHT =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_TOP_RIGHT,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_MIDDLE =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_CENTER,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_BOTTOM_RIGHT =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_BOTTOM_RIGHT,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_TOP_LEFT =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_TOP_LEFT,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_BOTTOM_LEFT =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_BOTTOM_LEFT,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_TILE =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_TILE,
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_POSITION_STRETCH =>
                \Magento\Framework\Image\Adapter\AbstractAdapter::POSITION_STRETCH,
        ];

        /** @var \Magento\Framework\Image $image */
        $image = $this->imageFactory->create([
            'adapter' => $this->gd2AdapterFactory->create(),
            'fileName' => $imageObj->getPath(),
        ]);
        $imageOriginalHeight = $image->getOriginalHeight();
        $image->open();

        $image->keepTransparency(true);

        $image->setWatermarkPosition($watermarkPositions[$this->getEbayDescriptionTemplate()->getWatermarkPosition()]);

        /** @var \Magento\Framework\Image $watermark */
        $watermark = $this->imageFactory->create([
            'adapter' => $this->gd2AdapterFactory->create(),
            'fileName' => $watermarkPath,
        ]);
        $watermarkOriginalHeight = $watermark->getOriginalHeight();

        if ((int)$imageOriginalHeight === 0 || (int)$watermarkOriginalHeight === 0) {
            return;
        }

        $opacity = 100;
        if ($this->getEbayDescriptionTemplate()->isWatermarkTransparentEnabled()) {
            $opacity = $this->getEbayDescriptionTemplate()->getWatermarkOpacityLevel();
        }

        $image->setWatermarkImageOpacity($opacity);

        /**
         * Fix magento resize bug
         * @link https://github.com/magento/magento2/issues/35535
         * @link https://github.com/magento/magento2/commit/5131a551e96c6495c3b01ee5e728e211bb994d39
         */
        set_error_handler(function () {
            return true;
        }, E_DEPRECATED);
        try {
            $image->watermark($watermarkPath);
        } finally {
            restore_error_handler();
        }

        $image->save($markingImagePath);

        if (!$fileDriver->isFile($markingImagePath)) {
            return;
        }

        $imageObj->setPath($markingImagePath)
                 ->setUrl($imageObj->getUrlByPath())
                 ->resetHash();

        $imageObj->markAsHasWatermark();
    }

    /**
     * @param $description
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function addWatermarkForCustomDescription(&$description): void
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

    // ----------------------------------------

    private function findProductAttributeValue(string $attributeKey): ?string
    {
        $value = $this->getMagentoProduct()
                      ->getAttributeValue($attributeKey);

        return !empty($value) ? $value : null;
    }
}
