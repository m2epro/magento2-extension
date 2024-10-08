<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

class SynchGetExecutingInfo extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace
{
    private \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $synchronizationFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $synchronizationFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->synchronizationFactory = $synchronizationFactory;
    }

    public function execute()
    {
        $synchronization = $this->synchronizationFactory->create();
        if (!$synchronization->isLocked()) {
            $this->setJsonContent(['mode' => 'inactive']);

            return $this->getResult();
        }

        $contentData = $synchronization->getLockItemManager()->getContentData();
        $progressData = $contentData[\Ess\M2ePro\Model\Lock\Item\Progress::CONTENT_DATA_KEY];

        $response = ['mode' => 'executing'];

        if (!empty($progressData)) {
            $response['title'] = 'Marketplace Synchronization';
            $response['percents'] = $progressData[key($progressData)]['percentage'];
            $response['status'] = key($progressData);
        }

        $this->setJsonContent($response);

        return $this->getResult();
    }
}
