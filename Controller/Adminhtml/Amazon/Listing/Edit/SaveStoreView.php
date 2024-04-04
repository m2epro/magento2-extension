<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Edit;

class SaveStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\ChangeStoreService */
    private $changeStoreService;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\ChangeStoreService $changeStoreService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->amazonFactory = $amazonFactory;
        $this->changeStoreService = $changeStoreService;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['id'])) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $params['id']);
        $storeId = (int)$params['store_id'];

        $this->changeStoreService->change($listing, $storeId);

        return $this->getResult();
    }
}
