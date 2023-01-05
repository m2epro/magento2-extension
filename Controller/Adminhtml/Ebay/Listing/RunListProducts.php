<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class RunListProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\ListAction */
    private $realtimeListAction;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\ListAction */
    private $scheduledListAction;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\ListAction $realtimeListAction,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\ListAction $scheduledListAction,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($translationHelper, $listingLogResource, $ebayFactory, $context);
        $this->realtimeListAction = $realtimeListAction;
        $this->scheduledListAction = $scheduledListAction;
    }

    public function execute()
    {
        if ($this->isRealtimeProcess()) {
            return $this->processRealtime(
                $this->realtimeListAction
            );
        }

        return $this->createScheduleAction(
            $this->scheduledListAction
        );
    }
}
