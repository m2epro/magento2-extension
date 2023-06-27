<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AllItems\Actions;

class RunReviseProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\ReviseAction */
    private $realtimeReviseAction;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\ReviseAction */
    private $scheduleReviseAction;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\ReviseAction $realtimeReviseAction,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\ReviseAction $scheduleReviseAction,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($translationHelper, $listingLogResource, $ebayFactory, $context);

        $this->realtimeReviseAction = $realtimeReviseAction;
        $this->scheduleReviseAction = $scheduleReviseAction;
    }

    public function execute()
    {
        if ($this->isRealtimeProcess()) {
            return $this->processRealtime(
                $this->realtimeReviseAction
            );
        }

        return $this->createScheduleAction(
            $this->scheduleReviseAction
        );
    }
}
