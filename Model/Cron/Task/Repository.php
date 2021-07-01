<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

/**
 * Class Ess\M2ePro\Model\Cron\Task\Repository
 */
class Repository extends \Ess\M2ePro\Model\AbstractModel
{
    const COMPONENT_GENERAL = 'general';

    const GROUP_SYSTEM  = 'system';
    const GROUP_EBAY    = 'ebay';
    const GROUP_AMAZON  = 'amazon';
    const GROUP_WALMART = 'walmart';

    /** @var array */
    public static $registeredTasks = [
        \Ess\M2ePro\Model\Cron\Task\System\HealthStatus::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\ArchiveOldOrders::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\ClearOldLogs::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessPartial::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\ConnectorCommandPending\ProcessSingle::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessPartial::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\System\RequestPending\ProcessSingle::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        //todo maybe not!
        \Ess\M2ePro\Model\Cron\Task\System\Processing\ProcessResult::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
            'can-work-parallel' => true
        ],
        \Ess\M2ePro\Model\Cron\Task\System\Servicing\Synchronize::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyAdded::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyDeleted::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Magento\Product\BulkWebsiteUpdated::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectSpecialPriceEndDate::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Magento\GlobalNotifications::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
        ],
        \Ess\M2ePro\Model\Cron\Task\Listing\Product\StopQueue::NICK => [
            'component' => self::COMPONENT_GENERAL,
            'group'     => self::GROUP_SYSTEM,
            'can-work-parallel' => true
        ],

        //----------------------------------------

        \Ess\M2ePro\Model\Cron\Task\Ebay\UpdateAccountsPreferences::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Template\RemoveUnused::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Channel\SynchronizeChanges::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\DownloadNew::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\SendResponse::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\ResolveNonReceivedData::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessInstructions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessScheduledActions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
            'can-work-parallel' => true
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\RemovePotentialDuplicates::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\CreateFailed::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\UploadByUser::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Update::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\ReserveCancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Cancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Refund::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\ScheduleForUpdate::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],
        \Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore\UpdateOnChannel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'group'     => self::GROUP_EBAY,
        ],

        //----------------------------------------

        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Other\ResolveTitle::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\SynchronizeInventory::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\RunVariationParentProcessors::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
            'can-work-parallel' => true
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessInstructions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActionsResults::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\Details::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceDataReport::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\CreateFailed::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\UploadByUser::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\SellerOrderId::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Refund::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Cancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\ReserveCancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\SendInvoice::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
            'can-work-parallel' => true
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessUpdate::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessRefund::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessCancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessResults::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\InspectProducts::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\UpdateSettings::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
        ],
        \Ess\M2ePro\Model\Cron\Task\Amazon\Repricing\Synchronize::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'group'     => self::GROUP_AMAZON,
            'can-work-parallel' => true
        ],

        //----------------------------------------

        \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\SynchronizeInventory::NICK    => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessInstructions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActionsResults::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessListActions::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Receive::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\CreateFailed::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\UploadByUser::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Acknowledge::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Shipping::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Cancel::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
        \Ess\M2ePro\Model\Cron\Task\Walmart\Order\Refund::NICK => [
            'component' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'group'     => self::GROUP_WALMART,
        ],
    ];

    /** @var array */
    protected $_groupedTasks = [];

    //########################################

    public function getTaskMetadata($nick)
    {
        if (!isset(self::$registeredTasks[$nick])) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Unknown task nick [{$nick}]");
        }

        return self::$registeredTasks[$nick];
    }

    public function getTaskComponent($nick)
    {
        $meta = $this->getTaskMetadata($nick);
        return $meta['component'];
    }

    public function getTaskGroup($nick)
    {
        $meta = $this->getTaskMetadata($nick);
        return $meta['group'];
    }

    public function getTaskCanWorkInParallel($nick)
    {
        $meta = $this->getTaskMetadata($nick);
        return isset($meta['can-work-parallel']) && $meta['can-work-parallel'];
    }

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        foreach (self::$registeredTasks as $key => $task) {
            $this->_groupedTasks['components'][$task['component']][$key] = $task;
            $this->_groupedTasks['groups'][$task['group']][$key] = $task;

            if (!empty($task['can-work-parallel'])) {
                $this->_groupedTasks['parallel'][$key] = $task;
            }
        }

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getRegisteredTasks()
    {
        return array_keys(self::$registeredTasks);
    }

    public function getComponentTasks($component)
    {
        return isset($this->_groupedTasks['components'][$component])
            ? array_keys($this->_groupedTasks['components'][$component])
            : [];
    }

    public function getGroupTasks($group)
    {
        return isset($this->_groupedTasks['groups'][$group])
            ? array_keys($this->_groupedTasks['groups'][$group])
            : [];
    }

    public function getParallelTasks()
    {
        return isset($this->_groupedTasks['parallel']) ? array_keys($this->_groupedTasks['parallel']) : [];
    }

    //########################################

    public function getRegisteredComponents()
    {
        return [
            self::COMPONENT_GENERAL,
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
        ];
    }

    public function getRegisteredGroups()
    {
        return [
            self::GROUP_SYSTEM,
            self::GROUP_EBAY,
            self::GROUP_AMAZON,
            self::GROUP_WALMART,
        ];
    }

    //########################################
}
