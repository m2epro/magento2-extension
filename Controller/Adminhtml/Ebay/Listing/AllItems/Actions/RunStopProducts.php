<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AllItems\Actions;

class RunStopProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\StopAction */
    private $realtimeStopAction;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\StopAction */
    private $scheduledStopAction;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\StopAction $realtimeStopAction,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\StopAction $scheduledStopAction,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($translationHelper, $listingLogResource, $ebayFactory, $context);

        $this->realtimeStopAction = $realtimeStopAction;
        $this->scheduledStopAction = $scheduledStopAction;
    }

    public function execute()
    {
        if ($this->isRealtimeProcess()) {
            return $this->processRealtime(
                $this->realtimeStopAction
            );
        }

        return $this->createScheduleAction(
            $this->scheduledStopAction
        );
    }
}
