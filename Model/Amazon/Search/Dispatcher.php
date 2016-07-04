<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param $query
     * @return array|bool
     */
    public function runCustom(\Ess\M2ePro\Model\Listing\Product $listingProduct, $query)
    {
        if (empty($query)) {
            return false;
        }

        try {

            /** @var \Ess\M2ePro\Model\Amazon\Search\Custom $customSearch */
            $customSearch = $this->modelFactory->getObject('Amazon\Search\Custom');
            $customSearch->setListingProduct($listingProduct);
            $customSearch->setQuery($query);

            $searchResult = $customSearch->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $searchResult = false;
        }

        return $searchResult;
    }

    /**
     * @param array $listingsProducts
     * @return bool
     */
    public function runSettings(array $listingsProducts)
    {
        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($listingsProducts as $key => $listingProduct) {

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                unset($listingsProducts[$key]);
                continue;
            }

            if (!$this->checkSearchConditions($listingProduct)) {
                unset($listingsProducts[$key]);
                continue;
            }
        }

        if (empty($listingsProducts)) {
            return false;
        }

        try {

            /** @var \Ess\M2ePro\Model\Amazon\Search\Settings $settingsSearch */
            $settingsSearch = $this->modelFactory->getObject('Amazon\Search\Settings');

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            foreach ($listingsProducts as $listingProduct) {
                $settingsSearch->setListingProduct($listingProduct);
                $settingsSearch->resetStep();
                $settingsSearch->process();
            }

        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            return false;
        }

        return true;
    }

    //########################################

    private function checkSearchConditions(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        return $listingProduct->isNotListed() &&
               !$amazonListingProduct->isGeneralIdOwner() &&
               !$amazonListingProduct->getGeneralId();
    }

    //########################################
}