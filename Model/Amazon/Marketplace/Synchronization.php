<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Marketplace;

class Synchronization extends \Ess\M2ePro\Model\AbstractModel
{
    public const LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME = 1800; // 30 min

    /** @var \Ess\M2ePro\Model\Marketplace */
    protected $marketplace = null;
    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    protected $lockItemManager = null;
    /** @var \Ess\M2ePro\Model\Lock\Item\Progress */
    protected $progressManager = null;
    /** @var \Ess\M2ePro\Model\Synchronization\Log  */
    protected $synchronizationLog = null;
    /** @var array */
    private $productTypes = [];
    /** @var array */
    private $existingProductTypesNicks = [];
    /** @var array */
    private $newProductTypesNicks = [];

    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /**
     * @param \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->productTypeHelper = $productTypeHelper;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $this->marketplace = $marketplace;

        return $this;
    }

    public function isLocked()
    {
        if (!$this->getLockItemManager()->isExist()) {
            return false;
        }

        if ($this->getLockItemManager()->isInactiveMoreThanSeconds(self::LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME)) {
            $this->getLockItemManager()->remove();

            return false;
        }

        return true;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(): void
    {
        $this->getLockItemManager()->create();
        $this->getProgressManager()->setPercentage(0);

        $this->prepareExistingProductTypesNicks();
        $this->processDetails();
        $removedProductTypes = array_diff($this->existingProductTypesNicks, $this->newProductTypesNicks);
        $this->removedOldProductTypes($removedProductTypes);

        $specificsSteps = $this->getSpecificsStepsCount();
        $steps = 1 + $specificsSteps; // 1 step for details, other steps for specifics
        $percentsPerStep = (int)floor(100 / $steps);

        $this->getProgressManager()->setPercentage($percentsPerStep);

        for ($i = 0; $i < $specificsSteps; $i++) {
            $this->processSpecificsStep($i);
            $this->getProgressManager()->setPercentage($percentsPerStep * (2 + $i));
        }

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');

        $this->getProgressManager()->setPercentage(100);

        $this->getLockItemManager()->remove();
    }

    private function processDetails(): void
    {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObj */
        $dispatcherObj = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            [
                'include_details' => true,
                'marketplace' => $this->marketplace->getNativeId(),
            ],
            'info',
            null
        );

        $dispatcherObj->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if ($details === null) {
            return;
        }

        $tableMarketplaces = $this->getHelper('Module_Database_Structure')
                                  ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

        $this->resourceConnection->getConnection()->delete(
            $tableMarketplaces,
            ['marketplace_id = ?' => $this->marketplace->getId()]
        );

        // todo \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace
        $data = [
            'marketplace_id' => $this->marketplace->getId(),
            'client_details_last_update_date' => $details['last_update'] ?? null,
            'server_details_last_update_date' => $details['last_update'] ?? null,
            'product_types' => \Ess\M2ePro\Helper\Json::encode(
                $this->prepareProductTypes($details['details']['product_type'])
            ),
        ];

        $this->resourceConnection->getConnection()->insert($tableMarketplaces, $data);

        $this->prepareNewProductTypesNicks($details['details']['product_type']);
    }

    /**
     * @param array $productTypes
     *
     * @return array
     */
    private function prepareProductTypes(array $productTypes): array
    {
        $result = [];

        foreach ($productTypes as $productType) {
            $result[$productType['nick']] = $productType;
        }

        return $result;
    }

    /**
     * @return void
     */
    private function prepareExistingProductTypesNicks(): void
    {
        $productTypes = $this->productTypeHelper->getProductTypesInDictionary(
            (int)$this->marketplace->getId()
        );

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productType */
        foreach ($productTypes as $productType) {
            $this->existingProductTypesNicks[] = $productType->getNick();
        }
    }

    private function prepareNewProductTypesNicks(array $productTypes): void
    {
        foreach ($productTypes as $productType) {
            $this->newProductTypesNicks[] = $productType['nick'];
        }
    }

    /**
     * @param array $removedProductTypes
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function removedOldProductTypes(array $removedProductTypes): void
    {
        if (empty($removedProductTypes)) {
            return;
        }

        $listToMarkInvalid = [];
        $listToRemove = [];
        $configuredProductTypes = $this->productTypeHelper
            ->getConfiguredProductTypesList((int)$this->marketplace->getId());

        foreach ($removedProductTypes as $nick) {
            if (isset($configuredProductTypes[$nick])) {
                $listToMarkInvalid[] = $nick;
            } else {
                $listToRemove[] = $nick;
            }
        }

        if (!empty($listToMarkInvalid)) {
            $this->productTypeHelper->markProductTypeDictionariesInvalid(
                (int)$this->marketplace->getId(),
                $listToMarkInvalid
            );
        }

        if (!empty($listToRemove)) {
            $this->productTypeHelper->removeProductTypeDictionaries(
                (int)$this->marketplace->getId(),
                $listToRemove
            );
        }
    }

    /**
     * @return int
     */
    private function getSpecificsStepsCount(): int
    {
        $this->productTypes = array_values(
            $this->productTypeHelper->getProductTypesInDictionary(
                (int)$this->marketplace->getId(),
                true
            )
        );

        return count($this->productTypes);
    }

    /**
     * @param int $step
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function processSpecificsStep(int $step): void
    {
        if (empty($this->productTypes[$step])) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $productTypeDictionary */
        $productTypeDictionary = $this->productTypes[$step];

        $this->productTypeHelper->updateProductTypeDictionary(
            $productTypeDictionary,
            $this->marketplace->getId(),
            $productTypeDictionary->getNick()
        );
    }

    public function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        return $this->lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK,
        ]);
    }

    public function getProgressManager()
    {
        if ($this->progressManager !== null) {
            return $this->progressManager;
        }

        return $this->progressManager = $this->modelFactory->getObject('Lock_Item_Progress', [
            'lockItemManager' => $this->getLockItemManager(),
            'progressNick' => '',
        ]);
    }

    public function getLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_MARKETPLACES);

        return $this->synchronizationLog;
    }
}
