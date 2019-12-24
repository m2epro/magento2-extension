<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\Description
 */
class Description extends \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Request\AbstractModel
{
    const PRODUCT_DETAILS_DOES_NOT_APPLY = 'Does Not Apply';
    const PRODUCT_DETAILS_UNBRANDED = 'Unbranded';

    const UPLOAD_IMAGES_MODE_AUTO = 1;
    const UPLOAD_IMAGES_MODE_SELF = 2;
    const UPLOAD_IMAGES_MODE_EPS  = 3;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplate = null;

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $data = [];

        if ($this->getConfigurator()->isGeneralAllowed()) {
            $data = array_merge(
                [
                    'hit_counter'          => $this->getEbayDescriptionTemplate()->getHitCounterType(),
                    'listing_enhancements' => $this->getEbayDescriptionTemplate()->getEnhancements(),
                    'item_condition_note'  => $this->getConditionNoteData(),
                    'product_details'      => $this->getProductDetailsData()
                ],
                $this->getConditionData()
            );
        }

        return array_merge(
            $data,
            $this->getTitleData(),
            $this->getSubtitleData(),
            $this->getDescriptionData(),
            $this->getImagesData()
        );
    }

    //########################################

    /**
     * @return array
     */
    public function getTitleData()
    {
        if (!$this->getConfigurator()->isTitleAllowed()) {
            return [];
        }

        $this->searchNotFoundAttributes();
        $data = $this->getDescriptionSource()->getTitle();
        $this->processNotFoundAttributes('Title');

        return [
            'title' => $data
        ];
    }

    /**
     * @return array
     */
    public function getSubtitleData()
    {
        if (!$this->getConfigurator()->isSubtitleAllowed()) {
            return [];
        }

        $this->searchNotFoundAttributes();
        $data = $this->getDescriptionSource()->getSubTitle();
        $this->processNotFoundAttributes('Subtitle');

        return [
            'subtitle' => $data
        ];
    }

    /**
     * @return array
     */
    public function getDescriptionData()
    {
        if (!$this->getConfigurator()->isDescriptionAllowed()) {
            return [];
        }

        $this->searchNotFoundAttributes();

        $data = $this->getDescriptionSource()->getDescription();
        $data = $this->getEbayListingProduct()->getDescriptionRenderer()->parseTemplate($data);

        $this->processNotFoundAttributes('Description');

        return [
            'description' => $data
        ];
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getImagesData()
    {
        if (!$this->getConfigurator()->isImagesAllowed()) {
            return [];
        }

        $this->searchNotFoundAttributes();

        $links = [];
        $galleryImages = $this->getDescriptionSource()->getGalleryImages();

        foreach ($galleryImages as $image) {
            if (!$image->getUrl()) {
                continue;
            }

            $links[] = $image->getUrl();
        }

        if (!empty($galleryImages)) {
            $this->addMetaData(
                'ebay_product_images_hash',
                $this->getHelper('Component_Ebay_Images')->getHash($galleryImages)
            );
        }

        $data = [
            'gallery_type' => $this->getEbayDescriptionTemplate()->getGalleryType(),
            'images'       => $links,
            'supersize'    => $this->getEbayDescriptionTemplate()->isUseSupersizeImagesEnabled()
        ];

        $this->processNotFoundAttributes('Main Image / Gallery Images');

        return [
            'images' => $data
        ];
    }

    //########################################

    /**
     * @return array
     */
    public function getProductDetailsData()
    {
        if ($this->getIsVariationItem()) {
            return [];
        }

        $data = [];

        foreach (['isbn','epid','upc','ean','brand','mpn'] as $tempType) {
            if ($this->getEbayDescriptionTemplate()->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($this->getEbayDescriptionTemplate()->isProductDetailsModeDoesNotApply($tempType)) {
                $data[$tempType] = ($tempType == 'brand') ? self::PRODUCT_DETAILS_UNBRANDED :
                                                            self::PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            $this->searchNotFoundAttributes();
            $tempValue = $this->getDescriptionSource()->getProductDetail($tempType);

            if (!$this->processNotFoundAttributes(strtoupper($tempType)) || !$tempValue) {
                continue;
            }

            $data[$tempType] = $tempValue;
        }

        $data = $this->deleteMPNifBrandIsNotSelected($data);
        $data = $this->deleteNotAllowedIdentifier($data);

        if (empty($data)) {
            return $data;
        }

        $data['include_description'] = $this->getEbayDescriptionTemplate()->isProductDetailsIncludeDescription();
        $data['include_image'] = $this->getEbayDescriptionTemplate()->isProductDetailsIncludeImage();

        return $data;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getConditionData()
    {
        $this->searchNotFoundAttributes();
        $data = $this->getDescriptionSource()->getCondition();

        if (!$this->processNotFoundAttributes('Condition')) {
            return [];
        }

        return [
            'item_condition' => $data
        ];
    }

    /**
     * @return string
     */
    public function getConditionNoteData()
    {
        $this->searchNotFoundAttributes();
        $data = $this->getDescriptionSource()->getConditionNote();
        $this->processNotFoundAttributes('Seller Notes');

        return $data;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    private function getDescriptionTemplate()
    {
        if ($this->descriptionTemplate === null) {
            $this->descriptionTemplate = $this->getListingProduct()
                                              ->getChildObject()
                                              ->getDescriptionTemplate();
        }
        return $this->descriptionTemplate;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     */
    private function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description\Source
     */
    private function getDescriptionSource()
    {
        return $this->getEbayListingProduct()->getDescriptionTemplateSource();
    }

    //########################################

    private function deleteMPNifBrandIsNotSelected(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        if (empty($data['brand'])) {
            unset($data['mpn']);
        } elseif ($data['brand'] == self::PRODUCT_DETAILS_UNBRANDED) {
            $data['mpn'] = self::PRODUCT_DETAILS_DOES_NOT_APPLY;
        } elseif (empty($data['mpn'])) {
            $data['mpn'] = self::PRODUCT_DETAILS_DOES_NOT_APPLY;
        }

        return $data;
    }

    private function deleteNotAllowedIdentifier(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        $categoryId = $this->getEbayListingProduct()->getCategoryTemplateSource()->getMainCategory();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = $this->getHelper('Component_Ebay_Category_Ebay')
                                   ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled =\Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean','upc','isbn','epid'] as $identifier) {
            $key = $identifier.'_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);

                // M2ePro\TRANSLATIONS
                // The value of %type% was not sent because it is not allowed in this Category
                $this->addWarningMessage(
                    $this->getHelper('Module\Translation')->__(
                        'The value of %type% was not sent because it is not allowed in this Category',
                        $this->getHelper('Module\Translation')->__(strtoupper($identifier))
                    )
                );
            }
        }

        return $data;
    }

    //########################################
}
