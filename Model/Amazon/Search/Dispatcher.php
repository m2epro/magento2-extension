<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

use Ess\M2ePro\Model\Amazon\Search\Custom\Result as CustomResult;

class Dispatcher
{
    /** @var \Ess\M2ePro\Model\Amazon\Search\Custom\Factory */
    private $customSearchFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Search\SettingsFactory */
    private $settingsSearchFactory;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Search\Custom\Factory $customSearchFactory,
        \Ess\M2ePro\Model\Amazon\Search\SettingsFactory $settingsSearchFactory,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
    ) {
        $this->customSearchFactory = $customSearchFactory;
        $this->settingsSearchFactory = $settingsSearchFactory;
        $this->exceptionHelper = $exceptionHelper;
    }

    /**
     * @param string $queryValue
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return \Ess\M2ePro\Model\Amazon\Search\Custom\Result
     */
    public function runCustom(string $queryValue, \Ess\M2ePro\Model\Listing\Product $listingProduct): CustomResult
    {
        $query = $this->customSearchFactory->createQuery($queryValue);
        $customSearch = $this->customSearchFactory->createHandler($query, $listingProduct);

        return $customSearch->process();
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingsProducts
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function runSettings(array $listingsProducts): bool
    {
        foreach ($listingsProducts as $key => $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                unset($listingsProducts[$key]);
                continue;
            }

            if (!$this->checkSearchConditions($listingProduct)) {
                unset($listingsProducts[$key]);
            }
        }

        if (empty($listingsProducts)) {
            return false;
        }

        try {
            $settingsSearch = $this->settingsSearchFactory->create();
            foreach ($listingsProducts as $listingProduct) {
                $settingsSearch->setListingProduct($listingProduct);
                $settingsSearch->resetStep();
                if ($settingsSearch->checkIdentifierValidity()) {
                    $settingsSearch->process();
                }
            }
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);

            return false;
        }

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function checkSearchConditions(\Ess\M2ePro\Model\Listing\Product $listingProduct): bool
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        return $listingProduct->isNotListed()
            && !$amazonListingProduct->isGeneralIdOwner()
            && !$amazonListingProduct->getGeneralId();
    }
}
