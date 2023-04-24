<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Template
 */
class Template extends AbstractModel
{
    //########################################

    protected function check()
    {
        return null;
    }

    protected function execute()
    {
        $productTypeTemplateId    = $this->getProcessor()->getAmazonListingProduct()->getTemplateProductTypeId();
        $shippingTemplateId       = $this->getProcessor()->getAmazonListingProduct()->getTemplateShippingId();
        $productTaxCodeTemplateId = $this->getProcessor()->getAmazonListingProduct()->getTemplateProductTaxCodeId();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $needSave = false;

            if ($amazonListingProduct->getTemplateProductTypeId() != $productTypeTemplateId) {
                $amazonListingProduct->setData('template_product_type_id', $productTypeTemplateId);
                $needSave = true;
            }

            if ($amazonListingProduct->getTemplateShippingId() != $shippingTemplateId) {
                $amazonListingProduct->setData('template_shipping_id', $shippingTemplateId);
                $needSave = true;
            }

            if ($amazonListingProduct->getTemplateProductTaxCodeId() != $productTaxCodeTemplateId) {
                $amazonListingProduct->setData('template_product_tax_code_id', $productTaxCodeTemplateId);
                $needSave = true;
            }

            $needSave && $amazonListingProduct->save();
        }
    }

    //########################################
}
