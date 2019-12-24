<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Template
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
        $categoryTemplateId = $this->getProcessor()->getWalmartListingProduct()->getTemplateCategoryId();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            $needSave = false;

            if ($walmartListingProduct->getTemplateCategoryId() != $categoryTemplateId) {
                $walmartListingProduct->setData('template_category_id', $categoryTemplateId);
                $needSave = true;
            }

            $needSave && $walmartListingProduct->save();
        }
    }

    //########################################
}
