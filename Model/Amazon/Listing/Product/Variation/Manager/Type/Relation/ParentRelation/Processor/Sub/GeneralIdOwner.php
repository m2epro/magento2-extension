<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class GeneralIdOwner extends AbstractModel
{
    //########################################

    protected function check()
    {
        if (!$this->getProcessor()->isGeneralIdSet() || !$this->getProcessor()->isGeneralIdOwner()) {
            $this->getProcessor()->getListingProduct()->getChildObject()->setData('sku', null);
        }

        if ($this->getProcessor()->isGeneralIdOwner() &&
            !$this->getProcessor()->getAmazonListingProduct()->isExistDescriptionTemplate()
        ) {
            $this->getProcessor()->getListingProduct()->getChildObject()->setData('is_general_id_owner', 0);
        }
    }

    protected function execute()
    {
        $isGeneralIdOwner = $this->getProcessor()->getAmazonListingProduct()->isGeneralIdOwner();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $needSave = false;

            if ($amazonListingProduct->isGeneralIdOwner() != $isGeneralIdOwner) {
                $amazonListingProduct->setData('is_general_id_owner', $isGeneralIdOwner);
                $needSave = true;
            }

            $needSave && $amazonListingProduct->save();
        }
    }

    //########################################
}