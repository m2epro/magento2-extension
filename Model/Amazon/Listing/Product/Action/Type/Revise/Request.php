<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise;

use Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request as ListActionRequest;
use Ess\M2ePro\Helper\Data\Product\Identifier;
use Ess\M2ePro\Model\Amazon\Template\ProductType as ProductTypeTemplate;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Revise\Request
 */
class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    //########################################

    protected function getActionData()
    {
        $data = array_merge(
            [
                'sku' => $this->getAmazonListingProduct()->getSku(),
            ],
            $this->getProductIdentifierData(),
            $this->getQtyData(),
            $this->getRegularPriceData(),
            $this->getBusinessPriceData(),
            $this->getDetailsData()
        );

        if ($this->getVariationManager()->isRelationParentType()) {
            $channelTheme = $this->getVariationManager()->getTypeModel()->getChannelTheme();

            $data['variation_data'] = [
                'parentage' => ListActionRequest::PARENTAGE_PARENT,
                'theme' => $channelTheme,
            ];
        } elseif ($this->getVariationManager()->isRelationChildType()) {
            $variationData = [
                'parentage' => ListActionRequest::PARENTAGE_CHILD,
                'attributes' => $this->getVariationManager()->getTypeModel()->getChannelOptions(),
            ];

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $parentAmazonListingProduct */
            $parentAmazonListingProduct = $this->getVariationManager()
                                               ->getTypeModel()
                                               ->getParentListingProduct()
                                               ->getChildObject();

            $parentSku = $parentAmazonListingProduct->getSku();
            if (!empty($parentSku)) {
                $variationData['parent_sku'] = $parentSku;
            }

            $channelTheme = $parentAmazonListingProduct->getVariationManager()->getTypeModel()->getChannelTheme();
            if (!empty($channelTheme)) {
                $variationData['theme'] = $channelTheme;
            }

            $data['variation_data'] = $variationData;
        }

        return $data;
    }

    private function getProductIdentifierData(): array
    {
        $productType = $this->getAmazonListingProduct()->getProductTypeTemplate();
        if (
            $productType === null
            || $productType->getNick() === ProductTypeTemplate::GENERAL_PRODUCT_TYPE_NICK
        ) {
            return [];
        }

        $productIdentifiers = $this->getAmazonListingProduct()->getIdentifiers();
        $data = [];

        if ($worldwideId = $productIdentifiers->getWorldwideId()) {
            $data['product_id'] = $worldwideId->getIdentifier();
            $data['product_id_type'] = $worldwideId->isUPC() ? Identifier::UPC : Identifier::EAN;
        }

        return $data;
    }
}
