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
        $changesData                      = [];
        $updatedListingProductsRepricing  = [];
        $updatedSkus                      = [];

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $changeData = $this->getChangeData($listingProductRepricing);
            if ($changeData && !in_array($changeData['sku'], $updatedSkus, true)) {
                $changesData[] = $changeData;
                $updatedSkus[] = $changeData['sku'];
                $updatedListingProductsRepricing[] = $listingProductRepricing;
            }
        }

        if (!$this->sendData($changesData)) {
            return false;
        }

        $this->updateListingsProductsRepricing($updatedListingProductsRepricing);

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

        if ($isDisabled   == $listingProductRepricing->getLastUpdatedIsDisabled() &&
            $regularPrice == $listingProductRepricing->getLastUpdatedRegularPrice() &&
            $minPrice     == $listingProductRepricing->getLastUpdatedMinPrice() &&
            $maxPrice     == $listingProductRepricing->getLastUpdatedMaxPrice()
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
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e, false);
            $this->getSynchronizationLog()->addMessageFromException($e);

            return false;
        }

        $this->processErrorMessages($result['response']);
        return true;
    }

    protected function updateListingsProductsRepricing(array $updatedProducts)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $updatedProduct */
        foreach ($updatedProducts as $updatedProduct) {
            $this->resourceConnection->getConnection()->update(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_amazon_listing_product_repricing'),
                [
                    'last_updated_regular_price' => $updatedProduct->getRegularPrice(),
                    'last_updated_min_price'     => $updatedProduct->getMinPrice(),
                    'last_updated_max_price'     => $updatedProduct->getMaxPrice(),
                    'last_updated_is_disabled'   => $updatedProduct->isDisabled(),
                    'update_date'                => $this->getHelper('Data')->getCurrentGmtDate(),
                ],
                ['listing_product_id = ?' => $updatedProduct->getListingProductId()]
            );
        }
    }

    //########################################
}
