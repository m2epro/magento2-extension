<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AllItems\Actions;

class RunRelistProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\RelistAction */
    private $realtimeRelistAction;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\RelistAction */
    private $scheduledRelistAction;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\RelistAction $realtimeRelistAction,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\RelistAction $scheduledRelistAction,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($translationHelper, $listingLogResource, $ebayFactory, $context);

        $this->realtimeRelistAction = $realtimeRelistAction;
        $this->scheduledRelistAction = $scheduledRelistAction;
    }

    public function execute()
    {
        if ($this->isRealtimeProcess()) {
            return $this->processRealtime(
                $this->realtimeRelistAction
            );
        }

        return $this->createScheduleAction(
            $this->scheduledRelistAction
        );
    }
}
