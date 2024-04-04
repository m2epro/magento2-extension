<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Account\FbaInventory;

use Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager as MerchantManager;

class MagentoSourceUpdater
{
    /** @var \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty\MerchantManager */
    private $merchantManager;
    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    private $searchCriteriaBuilder;
    /** @var \Magento\InventoryApi\Api\SourceItemRepositoryInterface|null */
    private $sourceItemRepository;
    /** @var \Magento\InventoryApi\Api\SourceItemsSaveInterface|null */
    private $sourceItemsSave;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\LogFactory */
    private $listingLogFactory;
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $logHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\Repository  */
    private $merchantSettingsRepository;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        MerchantManager $merchantManager,
        \Ess\M2ePro\Model\Amazon\Account\MerchantSetting\Repository $merchantSettingsRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\Amazon\Listing\LogFactory $listingLogFactory,
        \Ess\M2ePro\Helper\Module\Log $logHelper
    ) {
        $this->merchantManager = $merchantManager;
        $this->merchantManager->init();
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->magentoHelper = $magentoHelper;
        $this->listingLogFactory = $listingLogFactory;
        $this->logHelper = $logHelper;
        $this->sourceItemRepository = null;
        $this->sourceItemsSave = null;

        if ($this->magentoHelper->isMSISupportingVersion()) {
            $this->sourceItemRepository = $objectManager->get(
                \Magento\InventoryApi\Api\SourceItemRepositoryInterface::class
            );
            $this->sourceItemsSave = $objectManager->get(
                \Magento\InventoryApi\Api\SourceItemsSaveInterface::class
            );
        }
        $this->merchantSettingsRepository = $merchantSettingsRepository;
    }

    /**
     * @param string $merchantId
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProductItems
     * @param array $changedData
     *
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function updateQty(
        string $merchantId,
        array $listingProductItems,
        array $changedData
    ): void {
        if (
            !$this->magentoHelper->isMSISupportingVersion()
            || $this->sourceItemRepository === null
            || $this->sourceItemsSave === null
        ) {
            return;
        }

        $account = $this->findAccountWithEnabledFbaInventoryMode($merchantId);
        if ($account === null) {
            return;
        }

        $changedItems = $this->getChangedItems($listingProductItems, $changedData);
        if (empty($changedItems)) {
            return;
        }

        $searchCriteria = $this->buildSearchCriteria(
            $account->getMerchantSetting()->getManageFbaInventorySourceName(),
            $changedItems
        );

        $sourceItems = [];

        /** @var \Magento\Inventory\Model\SourceItem $sourceItem */
        foreach (
            $this->sourceItemRepository->getList($searchCriteria)
                                       ->getItems() as $sourceItem
        ) {
            $magentoSku = $sourceItem->getSku();
            $magentoLowerSku = strtolower($magentoSku);

            $newQty = (int)($changedItems[$magentoSku]['new_qty'] ?? $changedItems[$magentoLowerSku]['new_qty']);

            $sourceItem->setQuantity((float)$newQty);
            $sourceItems[] = $sourceItem;

            $this->logListingProductMessage(
                $changedItems[$magentoSku]['listing_product'],
                $sourceItem,
                (int)$changedItems[$magentoSku]['listing_product']->getChildObject()->getOnlineAfnQty(),
                $newQty
            );
        }

        if (empty($sourceItems)) {
            return;
        }

        $this->sourceItemsSave->execute($sourceItems);
    }

    /**
     * @param string $merchantId
     *
     * @return \Ess\M2ePro\Model\Amazon\Account|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function findAccountWithEnabledFbaInventoryMode(
        string $merchantId
    ): ?\Ess\M2ePro\Model\Amazon\Account {
        $settings = $this->merchantSettingsRepository->get($merchantId);
        if (!$settings->isManageFbaInventory()) {
            return null;
        }

        $accounts = $this->merchantManager->getMerchantAccounts($merchantId);

        return reset($accounts)->getChildObject();
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProductItems
     * @param array $changedData
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getChangedItems(
        array $listingProductItems,
        array $changedData
    ): array {
        $changedProductsInfo = [];

        foreach ($listingProductItems as $listingProduct) {
            $sku = (string)$listingProduct->getChildObject()->getSku();
            $lowerSku = strtolower($sku);

            if (
                isset($changedData[$sku])
                || isset($changedData[$lowerSku])
            ) {
                $oldOnlineAfnQty = (int)$listingProduct->getChildObject()->getOnlineAfnQty();
                $newOnlineAfnQty = (int)($changedData[$sku] ?? $changedData[$lowerSku]);

                if ($oldOnlineAfnQty !== $newOnlineAfnQty) {
                    $skuMagentoProduct = $listingProduct->getMagentoProduct()->getSku();
                    $changedProductsInfo[$skuMagentoProduct] = [
                        'listing_product' => $listingProduct,
                        'new_qty' => $newOnlineAfnQty,
                    ];
                }
            }
        }

        return $changedProductsInfo;
    }

    private function buildSearchCriteria(string $sourceCode, array $changedItems): \Magento\Framework\Api\SearchCriteria
    {
        return $this->searchCriteriaBuilder
            ->addFilter(\Magento\InventoryApi\Api\Data\SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->addFilter(\Magento\InventoryApi\Api\Data\SourceItemInterface::SKU, array_keys($changedItems), 'in')
            ->create();
    }

    private function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $sourceItem,
        int $oldValue,
        int $newValue
    ): void {
        $listingLog = $this->listingLogFactory->create();
        $listingLog->setComponentMode($listingProduct->getComponentMode());

        $listingLog->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY_IN_MAGENTO_SOURCE,
            $this->logHelper->encodeDescription(
                'FBA Product Quantity updated from [%from%] to [%to%] for Source [%source%]',
                ['!from' => $oldValue, '!to' => $newValue, '!source' => $sourceItem->getSourceCode()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }
}
