<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Status extends AbstractModel
{
    //########################################

    protected function check() {}

    protected function execute()
    {
        $childListingProducts = $this->getProcessor()->getTypeModel()->getChildListingsProducts();

        if (!$this->getProcessor()->isGeneralIdSet() || empty($childListingProducts)) {
            $this->getProcessor()->getListingProduct()->addData(array(
                'status'                   => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
            ))->getChildObject()->addData([
                'variation_child_statuses' => null
            ]);

            return;
        }

        $sameStatus = null;
        $isStatusSame = true;

        $resultStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;

        $childStatuses = array(
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => 0,
            \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => 0,
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => 0,
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => 0,
            \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN    => 0,
        );

        foreach ($childListingProducts as $childListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childListingProduct */

            $childStatus = $childListingProduct->getStatus();

            $childStatuses[$childStatus]++;

            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $resultStatus = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
                continue;
            }

            if (!$isStatusSame || $resultStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                continue;
            }

            if (is_null($sameStatus)) {
                $sameStatus = $childStatus;
                continue;
            }

            if ($childStatus != $sameStatus) {
                $isStatusSame = false;
            }
        }

        if ($isStatusSame && !is_null($sameStatus) &&
            $sameStatus != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        ) {
            $resultStatus = $sameStatus;
        }

        $this->getProcessor()->getListingProduct()->addData([
            'status' => $resultStatus,
        ])->getChildObject()->addData([
            'variation_child_statuses' => $this->getHelper('Data')->jsonEncode($childStatuses),
        ]);
    }

    //########################################
}