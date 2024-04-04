<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Edit;

class SaveStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\Walmart\Listing\ChangeStoreService */
    private $changeStoreService;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\ChangeStoreService $changeStoreService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->walmartFactory = $walmartFactory;
        $this->changeStoreService = $changeStoreService;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['id'])) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $params['id']);
        $storeId = (int)$params['store_id'];

        $this->changeStoreService->change($listing, $storeId);

        return $this->getResult();
    }
}
