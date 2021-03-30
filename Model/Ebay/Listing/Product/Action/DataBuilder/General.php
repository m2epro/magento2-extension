<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\General
 */
class General extends AbstractModel
{
    const LISTING_TYPE_AUCTION  = 'Chinese';
    const LISTING_TYPE_FIXED    = 'FixedPriceItem';

    const PRODUCT_DETAILS_DOES_NOT_APPLY = 'Does Not Apply';
    const PRODUCT_DETAILS_UNBRANDED = 'Unbranded';

    //########################################

    public function getBuilderData()
    {
        $data = [
            'duration' => $this->getEbayListingProduct()->getSellingFormatTemplateSource()->getDuration(),
            'is_private' => $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isPrivateListing(),
            'currency' => $this->getEbayMarketplace()->getCurrency(),
            'hit_counter'          => $this->getEbayListingProduct()->getEbayDescriptionTemplate()->getHitCounterType(),
            'listing_enhancements' => $this->getEbayListingProduct()->getEbayDescriptionTemplate()->getEnhancements(),
            'product_details'      => $this->getProductDetailsData()
        ];

        if ($this->getEbayListingProduct()->isListingTypeFixed()) {
            $data['listing_type'] = self::LISTING_TYPE_FIXED;
        } else {
            $data['listing_type'] = self::LISTING_TYPE_AUCTION;
        }

        if ($this->getEbayListingProduct()->getEbaySellingFormatTemplate()->isRestrictedToBusinessEnabled()) {
            $data['restricted_to_business'] = $this->getEbayListingProduct()
                ->getEbaySellingFormatTemplate()
                ->isRestrictedToBusinessEnabled();
        }

        return $data;
    }

    //########################################

    /**
     * @return array
     */
    protected function getProductDetailsData()
    {
        if ($this->isVariationItem) {
            return [];
        }

        $data = [];

        foreach (['isbn', 'epid', 'upc', 'ean', 'brand', 'mpn'] as $tempType) {
            if ($this->getEbayListingProduct()->getEbayDescriptionTemplate()->isProductDetailsModeNone($tempType)) {
                continue;
            }

            if ($this->getEbayListingProduct()
                     ->getEbayDescriptionTemplate()
                     ->isProductDetailsModeDoesNotApply($tempType)) {
                $data[$tempType] = ($tempType == 'brand') ? self::PRODUCT_DETAILS_UNBRANDED :
                    self::PRODUCT_DETAILS_DOES_NOT_APPLY;
                continue;
            }

            $this->searchNotFoundAttributes();
            $tempValue = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getProductDetail($tempType);

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

        $data['include_ebay_details'] = $this->getEbayListingProduct()
            ->getEbayDescriptionTemplate()
            ->isProductDetailsIncludeEbayDetails();
        $data['include_image'] = $this->getEbayListingProduct()
            ->getEbayDescriptionTemplate()
            ->isProductDetailsIncludeImage();

        return $data;
    }

    protected function deleteMPNifBrandIsNotSelected(array $data)
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

    protected function deleteNotAllowedIdentifier(array $data)
    {
        if (empty($data)) {
            return $data;
        }

        $categoryId = $this->getEbayListingProduct()->getCategoryTemplateSource()->getCategoryId();
        $marketplaceId = $this->getMarketplace()->getId();
        $categoryFeatures = $this->getHelper('Component_Ebay_Category_Ebay')
            ->getFeatures($categoryId, $marketplaceId);

        if (empty($categoryFeatures)) {
            return $data;
        }

        $statusDisabled = \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay::PRODUCT_IDENTIFIER_STATUS_DISABLED;

        foreach (['ean', 'upc', 'isbn', 'epid'] as $identifier) {
            $key = $identifier.'_enabled';
            if (!isset($categoryFeatures[$key]) || $categoryFeatures[$key] != $statusDisabled) {
                continue;
            }

            if (isset($data[$identifier])) {
                unset($data[$identifier]);

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
