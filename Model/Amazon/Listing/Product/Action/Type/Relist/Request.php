<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Request as ListActionRequest;

class Request extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Request
{
    //########################################

    protected function getActionData()
    {
        $data = array_merge(
            array(
                'sku' => $this->getAmazonListingProduct()->getSku()
            ),
            $this->getRequestQty()->getRequestData(),
            $this->getRequestPrice()->getRequestData(),
            $this->getRequestDetails()->getRequestData(),
            $this->getRequestImages()->getRequestData(),
            $this->getRequestShippingOverride()->getRequestData()
        );

        if ($this->getVariationManager()->isRelationChildType()) {
            $variationData = array(
                'parentage'  => ListActionRequest::PARENTAGE_CHILD,
                'attributes' => $this->getVariationManager()->getTypeModel()->getChannelOptions(),
            );

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

    //########################################
}