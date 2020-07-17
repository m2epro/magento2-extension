<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Analytics
 */
class Analytics extends \Ess\M2ePro\Model\Servicing\Task
{
    /** In bytes. It is equal 1 Mb */
    const REQUEST_SIZE_MAX = 1048576;

    protected $registry;
    protected $serializer;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager\Serializer $serializer,
        \Magento\Eav\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    ) {
        $this->registry = $registry;
        $this->serializer = $serializer;

        parent::__construct(
            $config,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'analytics';
    }

    //########################################

    /**
     * @return bool
     */
    public function isAllowed()
    {
        return $this->registry->isPlannedNow();
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        try {
            return $this->collectAnalytics();
        } catch (\Exception $e) {
            $this->getHelper('Module_Exception')->process($e);
            return ['analytics' => []];
        }
    }

    public function processResponseData(array $data)
    {
        return null;
    }

    //########################################

    protected function collectAnalytics()
    {
        if (!$this->registry->getStartedAt()) {
            $this->registry->markStarted();
        }

        $progress = [];
        $entities = [];

        foreach ($this->getEntitiesTypes() as $component => $entitiesTypes) {
            foreach ($entitiesTypes as $entityType) {

                /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\EntityManager $manager */
                $manager = $this->modelFactory->getObject(
                    'Servicing_Task_Analytics_EntityManager',
                    ['params' => ['component'  => $component, 'entityType' => $entityType]]
                );

                $progress[$manager->getEntityKey()] = false;

                if ($manager->isCompleted()) {
                    $progress[$manager->getEntityKey()] = true;
                    continue;
                }

                $iteration = 0;
                foreach ($manager->getEntities() as $item) {
                    /** @var \Ess\M2ePro\Model\ActiveRecord\AbstractModel $item */

                    if ($iteration && $iteration % 10 === 0 && $this->isEntitiesPackFull($entities)) {
                        break 3;
                    }

                    $entities[] = $this->serializer->serializeData($item, $manager);
                    $manager->setLastProcessedId($item->getId());
                    $iteration++;
                }
            }
        }

        if (!in_array(false, $progress)) {
            $this->registry->markFinished();
        }

        return [
            'analytics' => [
                'entities'    => $entities,
                'planned_at'  => $this->registry->getPlannedAt(),
                'started_at'  => $this->registry->getStartedAt(),
                'finished_at' => $this->registry->getFinishedAt(),
            ]
        ];
    }

    //########################################

    protected function getEntitiesTypes()
    {
        return [
            \Ess\M2ePro\Helper\Component\Amazon::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_Description',
                'Template_SellingFormat',
                'Amazon_Template_ProductTaxCode',
                'Amazon_Template_Shipping',
            ],
            \Ess\M2ePro\Helper\Component\Ebay::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_Description',
                'Template_SellingFormat',
                'Ebay_Template_ReturnPolicy',
                'Ebay_Template_Payment',
                'Ebay_Template_Shipping',
                'Ebay_Template_Category',
            ],
            \Ess\M2ePro\Helper\Component\Walmart::NICK => [
                'Account',
                'Listing',
                'Template_Synchronization',
                'Template_Description',
                'Template_SellingFormat',
                'Walmart_Template_Category'
            ]
        ];
    }

    protected function isEntitiesPackFull(&$entities)
    {
        $dataSize = strlen($this->getHelper('Data')->jsonEncode($entities));
        return $dataSize > self::REQUEST_SIZE_MAX;
    }

    //########################################
}
