<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Repricing\Updating
 */
class Updating extends AbstractModel
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing
     * @return bool|array
     */
    public function process(array $listingsProductsRepricing)
    {
        $changesData = [];
        $updatedSkus = [];

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $changeData = $this->getChangeData($listingProductRepricing);
            if ($changeData && !in_array($changeData['sku'], $updatedSkus, true)) {
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

        return [
            'sku' => $listingProductRepricing->getAmazonListingProduct()->getSku(),
            'regular_product_price'   => $regularPrice,
            'minimal_product_price'   => $minPrice,
            'maximal_product_price'   => $maxPrice,
            'is_calculation_disabled' => $isDisabled,
        ];
    }

    private function sendData(array $changesData)
    {
        try {
            $result = $this->getHelper('Component_Amazon_Repricing')->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE_USER_CHANGES,
                [
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                    'offers'        => $this->getHelper('Data')->jsonEncode($changesData),
                ]
            );
        } catch (\Exception $exception) {
            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception, false);
            return false;
        }

        $this->processErrorMessages($result['response']);
        return true;
    }

    //########################################
}
