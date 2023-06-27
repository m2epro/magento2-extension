<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing;

class Updating extends AbstractModel
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\LogFactory  */
    private $listingLogFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $helperAmazonRepricing;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @var array<int, array{
     *     listing_product_repricing: \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing,
     *     regular_product_price: float,
     *     minimal_product_price: float,
     *     maximal_product_price: float,
     *     is_calculation_disabled: bool
     * }>
     */
    private $logData = [];

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Amazon\Listing\LogFactory $listingLogFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $helperAmazonRepricing,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        parent::__construct($activeRecordFactory, $amazonFactory, $resourceConnection, $helperFactory, $modelFactory);

        $this->listingLogFactory = $listingLogFactory;
        $this->listingLogResource = $listingLogResource;
        $this->helperAmazonRepricing = $helperAmazonRepricing;
        $this->exceptionHelper = $exceptionHelper;
        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $listingsProductsRepricing
     *
     * @return array|false
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process(array $listingsProductsRepricing)
    {
        $changesData = [];
        $updatedListingProductsRepricing = [];
        $updatedSkus = [];

        foreach ($listingsProductsRepricing as $listingProductRepricing) {
            $changeData = $this->getChangeData($listingProductRepricing);
            if ($changeData && !in_array($changeData['sku'], $updatedSkus, true)) {
                $changesData[] = $changeData;
                $updatedListingProductsRepricing[] = $listingProductRepricing;
                $updatedSkus[] = $changeData['sku'];
                $this->addLogData($listingProductRepricing, $changeData);
            }
        }

        if (empty($changeData) || !$this->sendData($changesData)) {
            return false;
        }

        $this->updateListingsProductsRepricing($updatedListingProductsRepricing);
        $this->logListingsProductsRepricing();

        return $updatedSkus;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing
     * @param array $changedData
     *
     * @return void
     */
    private function addLogData(
        \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing,
        array $changedData
    ): void {
        $this->logData[] = [
            'listing_product_repricing' => $listingProductRepricing,
            'regular_product_price' => (float)$changedData['regular_product_price'],
            'minimal_product_price' => (float)$changedData['minimal_product_price'],
            'maximal_product_price' => (float)$changedData['maximal_product_price'],
            'is_calculation_disabled' => (bool)$changedData['is_calculation_disabled'],
        ];
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing
     *
     * @return null|array{
     *     sku: string,
     *     regular_product_price: float|int,
     *     minimal_product_price: float|int,
     *     maximal_product_price: float|int,
     *     is_calculation_disabled: bool
     * }
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getChangeData(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing): ?array
    {
        $isDisabled = $listingProductRepricing->isDisabled();

        if ($isDisabled && $listingProductRepricing->isOnlineDisabled()) {
            return null;
        }

        $regularPrice = $listingProductRepricing->getRegularPrice();
        $minPrice = $listingProductRepricing->getMinPrice();
        $maxPrice = $listingProductRepricing->getMaxPrice();

        if (
            (($regularPrice !== null) && empty($regularPrice))
            || (($minPrice !== null) && empty($minPrice))
            || (($maxPrice !== null) && empty($maxPrice))
        ) {
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                __(
                    'Item price was not updated. Incorrect Price value.'
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            return null;
        }

        $_regularPrice = $regularPrice;
        $_minPrice = $minPrice;
        $_maxPrice = $maxPrice;

        if (
            $isDisabled == $listingProductRepricing->getLastUpdatedIsDisabled() &&
            $regularPrice == $listingProductRepricing->getLastUpdatedRegularPrice() &&
            $minPrice == $listingProductRepricing->getLastUpdatedMinPrice() &&
            $maxPrice == $listingProductRepricing->getLastUpdatedMaxPrice()
        ) {
            return null;
        }

        if (!$regularPrice) {
            $regularPrice = (float)$listingProductRepricing->getOnlineRegularPrice();
        }
        if (!$minPrice) {
            $minPrice = (float)$listingProductRepricing->getOnlineMinPrice();
        }
        if (!$maxPrice) {
            $maxPrice = (float)$listingProductRepricing->getOnlineMaxPrice();
        }

        if (
            ($maxPrice !== null && $regularPrice !== null && $regularPrice > $maxPrice)
            ||
            ($minPrice !== null && $regularPrice !== null && $regularPrice < $minPrice)
        ) {
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                __(
                    'Item price was not updated. Regular Price must be lower than the Max Price value and higher than
                    the Min Price value.'
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            return null;
        }

        if ($minPrice !== null && $maxPrice !== null && $minPrice === $maxPrice) {
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                __(
                    'Item price was not updated. Min Price and Max Price can\'t be equal.'
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            return null;
        }

        return [
            'sku' => $listingProductRepricing->getAmazonListingProduct()->getSku(),
            'regular_product_price' => $_regularPrice,
            'minimal_product_price' => $_minPrice,
            'maximal_product_price' => $_maxPrice,
            'is_calculation_disabled' => $isDisabled,
        ];
    }

    /**
     * @param array $changesData
     *
     * @return bool
     */
    private function sendData(array $changesData): bool
    {
        try {
            $result = $this->helperAmazonRepricing->sendRequest(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_SYNCHRONIZE_USER_CHANGES,
                [
                    'account_token' => $this->getAmazonAccountRepricing()->getToken(),
                    'offers' => \Ess\M2ePro\Helper\Json::encode($changesData),
                ]
            );
        } catch (\Exception $e) {
            $this->exceptionHelper->process($e);
            $this->getSynchronizationLog()->addMessageFromException($e);

            return false;
        }

        $this->processErrorMessages($result['response']);

        return true;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing[] $updatedProducts
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function updateListingsProductsRepricing(array $updatedProducts): void
    {
        foreach ($updatedProducts as $updatedProduct) {
            $this->resourceConnection->getConnection()->update(
                $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_amazon_listing_product_repricing'),
                [
                    'last_updated_regular_price' => $updatedProduct->getRegularPrice(),
                    'last_updated_min_price' => $updatedProduct->getMinPrice(),
                    'last_updated_max_price' => $updatedProduct->getMaxPrice(),
                    'last_updated_is_disabled' => $updatedProduct->isDisabled(),
                    'update_date' => $this->dataHelper->getCurrentGmtDate(),
                ],
                ['listing_product_id = ?' => $updatedProduct->getListingProductId()]
            );
        }
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function logListingsProductsRepricing(): void
    {
        foreach ($this->logData as $logItem) {
            $listingProductRepricing = $logItem["listing_product_repricing"];
            $this->logListingProductMessage(
                $listingProductRepricing->getListingProduct(),
                __($this->getLogMessage($logItem)),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS
            );
        }
        $this->logData = [];
    }

    /**
     * @param array{
     *     listing_product_repricing: \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing,
     *     regular_product_price: float,
     *     minimal_product_price: float,
     *     maximal_product_price: float,
     *     is_calculation_disabled: bool
     * } $logItem
     *
     * @return string
     */
    private function getLogMessage(array $logItem): string
    {
        $message = 'Item was updated. ';

        $newUpdatedRegularPrice = $logItem["regular_product_price"];
        $newUpdatedMinPrice = $logItem['minimal_product_price'];
        $newUpdatedMaxPrice = $logItem['maximal_product_price'];
        $newUpdatedIsDisabled = $logItem['is_calculation_disabled'];

        $listingProductRepricing = $logItem["listing_product_repricing"];
        $lastUpdatedRegularPrice = (float)$listingProductRepricing->getLastUpdatedRegularPrice();
        $lastUpdatedMinPrice = (float)$listingProductRepricing->getLastUpdatedMinPrice();
        $lastUpdatedMaxPrice = (float)$listingProductRepricing->getLastUpdatedMaxPrice();
        $lastUpdatedIsDisabled = $listingProductRepricing->getLastUpdatedIsDisabled();

        $onlineRegularPrice = (float)$listingProductRepricing->getOnlineRegularPrice();
        $onlineMinPrice = (float)$listingProductRepricing->getOnlineMinPrice();
        $onlineMaxPrice = (float)$listingProductRepricing->getOnlineMaxPrice();

        // -----------------------------------------

        if (!$newUpdatedRegularPrice) {
            if (!$lastUpdatedRegularPrice) {
                $newUpdatedRegularPrice = $onlineRegularPrice;
            } else {
                $newUpdatedRegularPrice = $lastUpdatedRegularPrice;
            }
        }
        if (!$lastUpdatedRegularPrice) {
            $lastUpdatedRegularPrice = $onlineRegularPrice;
        }

        if ($newUpdatedRegularPrice !== $lastUpdatedRegularPrice) {
            $message .= 'Regular Price was changed from ' . $lastUpdatedRegularPrice . ' to '
                . $newUpdatedRegularPrice . '. ';
        }

        // -----------------------------------------

        if (!$newUpdatedMinPrice) {
            if (!$lastUpdatedMinPrice) {
                $newUpdatedMinPrice = $onlineMinPrice;
            } else {
                $newUpdatedMinPrice = $lastUpdatedMinPrice;
            }
        }
        if (!$lastUpdatedMinPrice) {
            $lastUpdatedMinPrice = $onlineMinPrice;
        }

        if (!$newUpdatedMaxPrice) {
            if (!$lastUpdatedMaxPrice) {
                $newUpdatedMaxPrice = $onlineMaxPrice;
            } else {
                $newUpdatedMaxPrice = $lastUpdatedMaxPrice;
            }
        }
        if (!$lastUpdatedMaxPrice) {
            $lastUpdatedMaxPrice = $onlineMaxPrice;
        }

        if (($newUpdatedMinPrice !== $lastUpdatedMinPrice) && ($newUpdatedMaxPrice !== $lastUpdatedMaxPrice)) {
            if (!(($newUpdatedMinPrice == $lastUpdatedMinPrice) && ($newUpdatedMaxPrice == $lastUpdatedMaxPrice))) {
                $message .= 'Min/Max Prices were changed from ' . $lastUpdatedMinPrice . ' to '
                    . $newUpdatedMinPrice . ' / from ' . $lastUpdatedMaxPrice . ' to ' . $newUpdatedMaxPrice . '. ';
            }
        } elseif (($newUpdatedMinPrice !== $lastUpdatedMinPrice) && ($newUpdatedMaxPrice === $lastUpdatedMaxPrice)) {
            $message .= 'Min Price was changed from ' . $lastUpdatedMinPrice . ' to ' . $newUpdatedMinPrice . '. ';
        } elseif (($newUpdatedMinPrice === $lastUpdatedMinPrice) && ($newUpdatedMaxPrice !== $lastUpdatedMaxPrice)) {
            $message .= 'Max Price was changed from ' . $lastUpdatedMaxPrice . ' to ' . $newUpdatedMaxPrice . '. ';
        }

        // -----------------------------------------

        if ($newUpdatedIsDisabled !== $lastUpdatedIsDisabled) {
            if ($newUpdatedIsDisabled) {
                $message .= 'Repricing was disabled.';
            } else {
                $message .= 'Repricing was enabled.';
            }
        }

        return $message;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $logMessage
     * @param int $logType
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        string $logMessage,
        int $logType
    ): void {
        $listingLog = $this->listingLogFactory->create();

        $listingLog->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            $this->listingLogResource->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_REPRICER,
            $logMessage,
            $logType
        );
    }
}
