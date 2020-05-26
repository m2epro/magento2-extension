<?php

/*
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
        $descriptionTemplateId    = $this->getProcessor()->getAmazonListingProduct()->getTemplateDescriptionId();
        $shippingTemplateId       = $this->getProcessor()->getAmazonListingProduct()->getTemplateShippingId();
        $productTaxCodeTemplateId = $this->getProcessor()->getAmazonListingProduct()->getTemplateProductTaxCodeId();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $needSave = false;

            if ($amazonListingProduct->getTemplateDescriptionId() != $descriptionTemplateId) {
                $amazonListingProduct->setData('template_description_id', $descriptionTemplateId);
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
