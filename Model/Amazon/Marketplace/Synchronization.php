<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Marketplace;

/**
 * Class \Ess\M2ePro\Model\Amazon\Marketplace\Synchronization
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

        $this->getProgressManager()->setPercentage(60);

        $this->processSpecifics();

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('marketplace');

        $this->getProgressManager()->setPercentage(100);

        $this->getLockItemManager()->remove();
    }

    //########################################

    protected function processDetails()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObj */
        $dispatcherObj = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj  = $dispatcherObj->getVirtualConnector(
            'marketplace',
            'get',
            'info',
            [
                'include_details' => true,
                'marketplace' => $this->marketplace->getNativeId()
            ],
            'info',
            null
        );

        $dispatcherObj->process($connectorObj);
        $details = $connectorObj->getResponseData();

        if ($details === null) {
            return;
        }

        $details['details']['last_update'] = $details['last_update'];
        $details = $details['details'];

        $tableMarketplaces = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

        $this->resourceConnection->getConnection()->delete(
            $tableMarketplaces,
            ['marketplace_id = ?' => $this->marketplace->getId()]
        );

        $helper = $this->getHelper('Data');

        $data = [
            'marketplace_id' => $this->marketplace->getId(),
            'client_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'server_details_last_update_date' => isset($details['last_update']) ? $details['last_update'] : null,
            'product_data' => isset($details['product_data']) ? $helper->jsonEncode($details['product_data']) : null,
        ];

        $this->resourceConnection->getConnection()->insert($tableMarketplaces, $data);
    }

    protected function processCategories()
    {
        $tableCategories = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_amazon_dictionary_category'
        );
        $this->resourceConnection->getConnection()->delete(
            $tableCategories,
            ['marketplace_id = ?' => $this->marketplace->getId()]
        );

        $tableProductData = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_amazon_dictionary_category_product_data'
        );
        $this->resourceConnection->getConnection()->delete(
            $tableProductData,
            ['marketplace_id = ?' => $this->marketplace->getId()]
        );

        $partNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObj */
            $dispatcherObj = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj  = $dispatcherObj->getVirtualConnector(
                'marketplace',
                'get',
                'categories',
                [
                    'part_number' => $partNumber,
                    'marketplace' => $this->marketplace->getNativeId()
                ],
                null,
                null
            );

            $dispatcherObj->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($response === null || empty($response['data'])) {
                break;
            }

            $insertData = [];

            $helper = $this->getHelper('Data');
            $responseDataCount = count($response['data']);

            for ($categoryIndex = 0; $categoryIndex < $responseDataCount; $categoryIndex++) {
                $data = $response['data'][$categoryIndex];

                $isLeaf = $data['is_leaf'];
                $insertData[] = [
                    'marketplace_id'     => $this->marketplace->getId(),
                    'category_id'        => $data['id'],
                    'parent_category_id' => $data['parent_id'],
                    'browsenode_id'      => ($isLeaf ? $data['browsenode_id'] : null),
                    'product_data_nicks' => ($isLeaf ? $helper->jsonEncode($data['product_data_nicks']) : null),
                    'title'              => $data['title'],
                    'path'               => $data['path'],
                    'keywords'           => ($isLeaf ? $helper->jsonEncode($data['keywords']) : null),
                    'is_leaf'            => $isLeaf,
                ];

                if (count($insertData) >= 100 || $categoryIndex >= (count($response['data']) - 1)) {
                    $this->resourceConnection->getConnection()->insertMultiple($tableCategories, $insertData);
                    $insertData = [];
                }
            }

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }
    }

    protected function processSpecifics()
    {
        $tableSpecifics = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_amazon_dictionary_specific'
        );
        $this->resourceConnection->getConnection()->delete(
            $tableSpecifics,
            ['marketplace_id = ?' => $this->marketplace->getId()]
        );

        $partNumber = 1;

        for ($i = 0; $i < 100; $i++) {
            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj     = $dispatcherObject->getVirtualConnector(
                'marketplace',
                'get',
                'specifics',
                [
                    'part_number' => $partNumber,
                    'marketplace' => $this->marketplace->getNativeId()
                ]
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if ($response === null || empty($response['data'])) {
                break;
            }

            $insertData = [];

            $helper = $this->getHelper('Data');
            $responseDataCount = count($response['data']);

            for ($specificIndex = 0; $specificIndex < $responseDataCount; $specificIndex++) {
                $data = $response['data'][$specificIndex];

                $insertData[] = [
                    'marketplace_id'     => $this->marketplace->getId(),
                    'specific_id'        => $data['id'],
                    'parent_specific_id' => $data['parent_id'],
                    'product_data_nick'  => $data['product_data_nick'],
                    'title'              => $data['title'],
                    'xml_tag'            => $data['xml_tag'],
                    'xpath'              => $data['xpath'],
                    'type'               => (int)$data['type'],
                    'values'             => $helper->jsonEncode($data['values']),
                    'recommended_values' => $helper->jsonEncode($data['recommended_values']),
                    'params'             => $helper->jsonEncode($data['params']),
                    'data_definition'    => $helper->jsonEncode($data['data_definition']),
                    'min_occurs'         => (int)$data['min_occurs'],
                    'max_occurs'         => (int)$data['max_occurs']
                ];

                if (count($insertData) >= 100 || $specificIndex >= (count($response['data']) - 1)) {
                    $this->resourceConnection->getConnection()->insertMultiple($tableSpecifics, $insertData);
                    $insertData = [];
                }
            }

            $partNumber = $response['next_part'];

            if ($partNumber === null) {
                break;
            }
        }
    }

    //########################################

    public function getLockItemManager()
    {
        if ($this->lockItemManager !== null) {
            return $this->lockItemManager;
        }

        return $this->lockItemManager = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_SYNCHRONIZATION_LOCK_ITEM_NICK
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
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_MARKETPLACES);

        return $this->synchronizationLog;
    }

    //########################################
}
