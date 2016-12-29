<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

class Updating extends AbstractModel
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing
     * @return bool|array
     */
    public function process(array $listingsProductsRepricing)
    {
        $changesData = array();
        $updatedSkus = array();

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            if ($changeData = $this->getChangeData($listingProductRepricing)) {
                $changesData[] = $changeData;
                $updatedSkus[] = $changeData['sku'];
            }
        }

        if (!$this->sendData($changesData)) {
            return false;
        }

        return $updatedSkus;
    }

    //########################################

    private function getChangeData(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing)
    {
        $isDisabled = $listingProductRepricing->isDisabled();

        if ($isDisabled && $listingProductRepricing->isOnlineDisabled()) {
            return false;
        }

        $regularPrice = $listingProductRepricing->getRegularPrice();
        $minPrice     = $listingProductRepricing->getMinPrice();
        $maxPrice     = $listingProductRepricing->getMaxPrice();

        if ($regularPrice == $listingProductRepricing->getOnlineRegularPrice() &&
            $minPrice     == $listingProductRepricing->getOnlineMinPrice() &&
            $maxPrice     == $listingProductRepricing->getOnlineMaxPrice() &&
            $isDisabled   == $listingProductRepricing->isOnlineDisabled()
        ) {
            return false;
        }

        return array(
            'sku' => $listingProductRepricing->getAmazonListingProduct()->getSku(),
            'regular_product_price'   => $regularPrice,
            'minimal_product_price'   => $minPrice,
            'maximal_product_price'   => $maxPrice,
            'is_calculation_disabled' => $isDisabled,
        );
    }

    private function sendData(array $changesData)
    {
        try {
            $this->getHelper('Component\Amazon\Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE_USER_CHANGES,
                array(
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                    'offers'        => $this->getHelper('Data')->jsonEncode($changesData),
                )
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        return true;
    }

    //########################################
}