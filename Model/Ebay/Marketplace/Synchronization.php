<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Marketplace;

/**
 * Class \Ess\M2ePro\Model\Ebay\Marketplace\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\AbstractModel
{
    const LOCK_ITEM_MAX_ALLOWED_INACTIVE_TIME = 1800; // 30 min

    /** @var \Ess\M2ePro\Model\Marketplace */
    protected $marketplace = null;

    /** @var \Ess\M2ePro\Model\Lock\Item\Manager */
    protected $lockItemManager = null;

    /** @var \Ess\M2ePro\Model\Lock\Item\Progress */
    protected $progressManager = null;

    /** @var \Ess\M2ePro\Model\Synchronization\Log  */
    protected $synchronizationLog = null;

    protected $activeRecordFactory;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $this->marketplace = $marketplace;
        return $this;
    }

    //########################################

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

    //########################################

    public function process()
    {
        $this->getLockItemManager()->create();

        $this->getProgressManager()->setPercentage(0);

        $this->processDetails();

        $this->getProgressManager()->setPercentage(10);

        $this->processCategories();

        $this->getProgressManager()->setPercentage(30);

        if ($this->getEbayMarketplace()->isEpidEnabled()) {
            $this->processEpids();
        }

        $this->getProgressManager()->setPercentage(70);

        if ($this->getEbayMarketplace()->isKtypeEnabled()) {
            $this->processKtypes();
        }

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');

        $this->getProgressManager()->setPercentage(100);

        $this->getLockItemManager()->remove();
    }

    //########################################

    protected function processDetails()
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            ['include_details' => 1],
            'info',
            $this->marketplace->getId(),
            null
        );

        $dispatcherObj->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if ($details === null) {
            return;
        }

        $details['details']['last_update'] = $details['last_update'];
        $details = $details['details'];

        $connection = $this->resourceConnection->getConnection();
        $dbHelper = $this->getHelper('Module_Database_Structure');

        $tableMarketplaces = $dbHelper->getTableNameWithPrefix('m2epro_ebay_dictionary_marketplace');
        $tableShipping = $dbHelper->getTableNameWithPrefix('m2epro_ebay_dictionary_shipping');

        // Save marketplaces
        // ---------------------------------------
        $connection->delete($tableMarketplaces, ['marketplace_id = ?' => $this->marketplace->getId()]);

        $helper = $this->getHelper('Data');

        $insertData = [
            'marketplace_id' => $this->marketplace->getId(),
            'client_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'server_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'dispatch' => $helper->jsonEncode($details['dispatch']),
            'packages' => $helper->jsonEncode($details['packages']),
            'return_policy' => $helper->jsonEncode($details['return_policy']),
            'listing_features' => $helper->jsonEncode($details['listing_features']),
            'payments' => $helper->jsonEncode($details['payments']),
            'shipping_locations' => $helper->jsonEncode($details['shipping_locations']),
            'shipping_locations_exclude' => $helper->jsonEncode($details['shipping_locations_exclude']),
            'tax_categories' => $helper->jsonEncode($details['tax_categories']),
            'charities' => $helper->jsonEncode($details['charities']),
        ];

        if (isset($details['additional_data'])) {
            $insertData['additional_data'] = $helper->jsonEncode($details['additional_data']);
        }

        unset($details['categories_version']);
        $connection->insert($tableMarketplaces, $insertData);
        // ---------------------------------------

        // Save shipping
        // ---------------------------------------
        $connection->delete($tableShipping, ['marketplace_id = ?' => $this->marketplace->getId()]);

        foreach ($details['shipping'] as $data) {
            $insertData = [
                'marketplace_id' => $this->marketplace->getId(),
                'ebay_id' => $data['ebay_id'],
                'title' => $data['title'],
                'category' => $helper->jsonEncode($data['category']),
                'is_flat' => $data['is_flat'],
                'is_calculated' => $data['is_calculated'],
                'is_international' => $data['is_international'],
                'data' => $helper->jsonEncode($data['data']),
            ];
            $connection->insert($tableShipping, $insertData);
        }

        // ---------------------------------------
    }

    protected function processCategories()
    {
        $connection = $this->resourceConnection->getConnection();

        $tableCategories = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_ebay_dictionary_category'
        );

        $connection->delete($tableCategories, ['marketplace_id = ?' => $this->marketplace->getId()]);
        $this->getHelper('Component_Ebay_Category')->removeEbayRecent();

        $partNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObj->getVirtualConnector(
                'marketplace',
                'get',
                'categories',
                ['part_number' => $partNumber],
                null,
                $this->marketplace->getId()
            );

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($response === null || empty($response['data'])) {
                break;
            }

            $connection = $this->resourceConnection->getConnection();
            $tableCategories = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_dictionary_category');

            $categoriesCount = count($response['data']);
            $insertData = [];

            $helper = $this->getHelper('Data');

            for ($categoryIndex = 0; $categoryIndex < $categoriesCount; $categoryIndex++) {
                $data = $response['data'][$categoryIndex];

                $insertData[] = [
                    'marketplace_id' => $this->marketplace->getId(),
                    'category_id' => $data['category_id'],
                    'parent_category_id' => $data['parent_id'],
                    'title' => $data['title'],
                    'path' => $data['path'],
                    'is_leaf' => $data['is_leaf'],
                    'features' => ($data['is_leaf'] ? $helper->jsonEncode($data['features']) : null)
                ];

                if (count($insertData) >= 100 || $categoryIndex >= ($categoriesCount - 1)) {
                    $connection->insertMultiple($tableCategories, $insertData);
                    $insertData = [];
                }
            }

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }
    }

    protected function processEpids()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableMotorsEpids = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_ebay_dictionary_motor_epid'
        );

        $helper = $this->getHelper('Component_Ebay_Motors');
        $scope = $helper->getEpidsScopeByType(
            $helper->getEpidsTypeByMarketplace(
                $this->marketplace->getId()
            )
        );

        $connection->delete(
            $tableMotorsEpids,
            [
                'is_custom = ?' => 0,
                'scope = ?' => $scope
            ]
        );

        $partNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObj->getVirtualConnector(
                'marketplace',
                'get',
                'motorsEpids',
                [
                    'marketplace' => $this->marketplace->getNativeId(),
                    'part_number' => $partNumber
                ]
            );

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($response === null || empty($response['data'])) {
                break;
            }

            $totalCountItems = count($response['data']['items']);
            if ($totalCountItems <= 0) {
                return;
            }

            $connection = $this->resourceConnection->getConnection();
            $tableMotorsEpids = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_ebay_dictionary_motor_epid');

            $temporaryIds = [];
            $itemsForInsert = [];

            $helper = $this->getHelper('Component_Ebay_Motors');
            $scope = $helper->getEpidsScopeByType(
                $helper->getEpidsTypeByMarketplace(
                    $this->marketplace->getId()
                )
            );

            for ($epidIndex = 0; $epidIndex < $totalCountItems; $epidIndex++) {
                $item = $response['data']['items'][$epidIndex];
                $temporaryIds[] = $item['ePID'];

                $itemsForInsert[] = [
                    'epid' => $item['ePID'],
                    'product_type' => (int)$item['product_type'],
                    'make' => $item['Make'],
                    'model' => $item['Model'],
                    'year' => $item['Year'],
                    'trim' => (isset($item['Trim']) ? $item['Trim'] : null),
                    'engine' => (isset($item['Engine']) ? $item['Engine'] : null),
                    'submodel' => (isset($item['Submodel']) ? $item['Submodel'] : null),
                    'street_name'  => (isset($item['StreetName']) ? $item['StreetName'] : null),
                    'scope' => $scope
                ];

                if (count($itemsForInsert) >= 100 || $epidIndex >= ($totalCountItems - 1)) {
                    $connection->insertMultiple($tableMotorsEpids, $itemsForInsert);
                    $connection->delete(
                        $tableMotorsEpids,
                        [
                            'is_custom = ?' => 1,
                            'epid IN (?)' => $temporaryIds
                        ]
                    );
                    $itemsForInsert = $temporaryIds = [];
                }
            }

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }
    }

    protected function processKtypes()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableMotorsKtypes = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_dictionary_motor_ktype');

        $connection->delete($tableMotorsKtypes, '`is_custom` = 0');

        $partNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObj->getVirtualConnector(
                'marketplace',
                'get',
                'motorsKtypes',
                ['part_number' => $partNumber],
                null,
                $this->marketplace->getId()
            );

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($response === null || empty($response['data'])) {
                break;
            }

            $totalCountItems = count($response['data']['items']);
            if ($totalCountItems <= 0) {
                return;
            }

            $connection = $this->resourceConnection->getConnection();
            $tableMotorsKtype = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
                'm2epro_ebay_dictionary_motor_ktype'
            );

            $temporaryIds = [];
            $itemsForInsert = [];

            for ($ktypeIndex = 0; $ktypeIndex < $totalCountItems; $ktypeIndex++) {
                $item = $response['data']['items'][$ktypeIndex];

                $temporaryIds[] = (int)$item['ktype'];
                $itemsForInsert[] = [
                    'ktype' => (int)$item['ktype'],
                    'make' => $item['make'],
                    'model' => $item['model'],
                    'variant' => $item['variant'],
                    'body_style' => $item['body_style'],
                    'type' => $item['type'],
                    'from_year' => (int)$item['from_year'],
                    'to_year' => (int)$item['to_year'],
                    'engine' => $item['engine'],
                ];

                if (count($itemsForInsert) >= 100 || $ktypeIndex >= ($totalCountItems - 1)) {
                    $connection->insertMultiple($tableMotorsKtype, $itemsForInsert);
                    $connection->delete(
                        $tableMotorsKtype,
                        [
                            'is_custom = ?' => 1,
                            'ktype IN (?)' => $temporaryIds
                        ]
                    );
                    $itemsForInsert = $temporaryIds = [];
                }
            }

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    protected function getEbayMarketplace()
    {
        return $this->marketplace->getChildObject();
    }

    //########################################

    public function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        return $this->lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Ebay::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
        ]);
    }

    public function getProgressManager()
    {
        if ($this->progressManager !== null) {
            return $this->progressManager;
        }

        return $this->progressManager = $this->modelFactory->getObject('Lock_Item_Progress', [
            'lockItemManager' => $this->getLockItemManager(),
            'progressNick'    => $this->marketplace->getTitle() . ' Marketplace'
        ]);
    }

    public function getLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_MARKETPLACES);

        return $this->synchronizationLog;
    }

    //########################################
}
