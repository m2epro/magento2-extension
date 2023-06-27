<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AllItems\Actions;

class RunStopAndRemoveProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\StopAndRemoveAction */
    private $realtimeStopAndRemoveAction;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\StopAndRemoveAction */
    private $scheduledStopAndRemoveAction;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\StopAndRemoveAction $realtimeStopAndRemoveAction,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\StopAndRemoveAction $scheduledStopAndRemoveAction,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($translationHelper, $listingLogResource, $ebayFactory, $context);

        $this->realtimeStopAndRemoveAction = $realtimeStopAndRemoveAction;
        $this->scheduledStopAndRemoveAction = $scheduledStopAndRemoveAction;
    }

    public function execute()
    {
        if ($this->isRealtimeProcess()) {
            return $this->processRealtime(
                $this->realtimeStopAndRemoveAction,
                ['remove' => true]
            );
        }

        return $this->createScheduleAction(
            $this->scheduledStopAndRemoveAction,
            ['remove' => true]
        );
    }
}
