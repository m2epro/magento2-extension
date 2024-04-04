<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Edit;

class SaveStoreView extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\ChangeStoreService */
    private $changeStoreService;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\ChangeStoreService $changeStoreService,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->ebayFactory = $ebayFactory;
        $this->changeStoreService = $changeStoreService;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params['id'])) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded('Listing', $params['id']);
        $storeId = (int)$params['store_id'];

        $this->changeStoreService->change($listing, $storeId);

        return $this->getResult();
    }
}
