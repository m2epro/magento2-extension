<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Template extends AbstractModel
{
    //########################################

    protected function check() {}

    protected function execute()
    {
        $descriptionTemplateId      = $this->getProcessor()->getAmazonListingProduct()->getTemplateDescriptionId();
        $shippingTemplateTemplateId = $this->getProcessor()->getAmazonListingProduct()->getTemplateShippingTemplateId();
        $shippingOverrideTemplateId = $this->getProcessor()->getAmazonListingProduct()->getTemplateShippingOverrideId();
        $productTaxCodeTemplateId   = $this->getProcessor()->getAmazonListingProduct()->getTemplateProductTaxCodeId();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $needSave = false;

            if ($amazonListingProduct->getTemplateDescriptionId() != $descriptionTemplateId) {
                $amazonListingProduct->setData('template_description_id', $descriptionTemplateId);
                $needSave = true;
            }

            if ($amazonListingProduct->getTemplateShippingTemplateId() != $shippingTemplateTemplateId) {
                $amazonListingProduct->setData('template_shipping_template_id', $shippingTemplateTemplateId);
                $needSave = true;
            }

            if ($amazonListingProduct->getTemplateShippingOverrideId() != $shippingOverrideTemplateId) {
                $amazonListingProduct->setData('template_shipping_override_id', $shippingOverrideTemplateId);
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