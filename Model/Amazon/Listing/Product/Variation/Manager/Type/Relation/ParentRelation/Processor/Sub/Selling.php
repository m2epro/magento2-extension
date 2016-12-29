<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Selling extends AbstractModel
{
    //########################################

    protected function check() {}

    protected function execute()
    {
        $qty = null;
        $price = null;

        $isAfn = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_NO;
        $isRepricing = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_REPRICING_NO;
        $repricingEnabled = $repricingDisabled = $afnCount = 0;

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
            if ($listingProduct->isNotListed()) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            if ($amazonListingProduct->isRepricingUsed()) {

                $isRepricing = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_REPRICING_YES;

                $amazonListingProduct->isRepricingDisabled() && $repricingDisabled++;
                $amazonListingProduct->isRepricingEnabled()  && $repricingEnabled++;
            }

            if ($amazonListingProduct->isAfnChannel()) {

                $isAfn = \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_YES;
                $afnCount++;

            } else {
                $qty = (int)$qty + (int)$amazonListingProduct->getOnlineQty();
            }

            $salePrice = (float)$amazonListingProduct->getOnlineSalePrice();
            $actualOnlinePrice = (float)$amazonListingProduct->getOnlinePrice();

            if ($salePrice > 0) {
                $startDateTimestamp = strtotime($amazonListingProduct->getOnlineSalePriceStartDate());
                $endDateTimestamp   = strtotime($amazonListingProduct->getOnlineSalePriceEndDate());

                $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false,'Y-m-d 00:00:00'));

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < $actualOnlinePrice
                ) {
                    $actualOnlinePrice = $salePrice;
                }
            }

            if (is_null($price) || $price > $actualOnlinePrice) {
                $price = $actualOnlinePrice;
            }
        }

        $this->getProcessor()->getListingProduct()->getChildObject()->addData(array(
            'online_qty'        => $qty,
            'online_price'      => $price,
            'is_afn_channel'    => $isAfn,
            'is_repricing'      => $isRepricing
        ));

        $this->getProcessor()->getListingProduct()->setSetting(
            'additional_data', 'repricing_enabled_count', $repricingEnabled
        );
        $this->getProcessor()->getListingProduct()->setSetting(
            'additional_data', 'repricing_disabled_count', $repricingDisabled
        );
        $this->getProcessor()->getListingProduct()->setSetting(
            'additional_data', 'afn_count', $afnCount
        );
    }

    //########################################
}