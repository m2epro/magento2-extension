<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search;

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
     * @param string $query
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     *
     * @return array|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function runCustom(string $query, \Ess\M2ePro\Model\Listing\Product $listingProduct): ?array
    {
        if (empty($query)) {
            return null;
        }

        try {
            $customSearch = $this->customSearchFactory->create($query, $listingProduct);
            $result = $customSearch->process();

            if ($result['data'] === false) {
                return null;
            }

            return $result;
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);
            return null;
        }
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
            /** @var \Ess\M2ePro\Model\Amazon\Search\Settings $settingsSearch */
            $settingsSearch = $this->settingsSearchFactory->create();
            foreach ($listingsProducts as $listingProduct) {
                $settingsSearch->setListingProduct($listingProduct);
                $settingsSearch->resetStep();
                $settingsSearch->process();
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
