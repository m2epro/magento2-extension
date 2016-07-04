<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

class MoveToListing extends Listing
{
    protected $parentFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        Context $context
    )
    {
        $this->parentFactory = $parentFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');

        $selectedProducts = (array)json_decode($this->getRequest()->getParam('selectedProducts'));
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,'Listing',$listingId
        );

        $otherLogModel = $this->activeRecordFactory->getObject('Listing\Other\Log');
        $otherLogModel->setComponentMode($componentMode);

        $listingLogModel = $this->activeRecordFactory->getObject('Listing\Log');
        $listingLogModel->setComponentMode($componentMode);

        $errors = 0;
        foreach ($selectedProducts as $otherListingProduct) {

            $otherListingProductInstance = $this->parentFactory
                ->getObjectLoaded($componentMode,'Listing\Other',$otherListingProduct);

            $listingProductInstance = $listingInstance
                ->getChildObject()
                ->addProductFromOther($otherListingProductInstance,false,false);

            if (!($listingProductInstance instanceof \Ess\M2ePro\Model\Listing\Product)) {

                $otherLogModel->addProductMessage(
                    $otherListingProductInstance->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    NULL,
                    \Ess\M2ePro\Model\Listing\Other\Log::ACTION_MOVE_LISTING,
                    // M2ePro_TRANSLATIONS
                    // Product already exists in M2E listing(s).
                    'Product already exists in M2E Pro listing(s).',
                    \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
                );

                $errors++;
                continue;
            }

            $otherLogModel->addProductMessage(
                $otherListingProductInstance->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                NULL,
                \Ess\M2ePro\Model\Listing\Other\Log::ACTION_MOVE_LISTING,
                // M2ePro_TRANSLATIONS
                // Item was successfully Moved
                'Item was successfully Moved',
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
            );

            $listingLogModel->addProductMessage(
                $listingId,
                $otherListingProductInstance->getProductId(),
                $listingProductInstance->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                NULL,
                \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_FROM_OTHER_LISTING,
                // M2ePro_TRANSLATIONS
                // Item was successfully Moved
                'Item was successfully Moved',
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractLog::PRIORITY_MEDIUM
            );

            $otherListingProductInstance->delete();
        };

        if ($errors == 0) {
            $this->setJsonContent(['result'=>'success']);
        } else {
            $this->setJsonContent(['result'=>'error', 'errors'=>$errors]);
        }

        return $this->getResult();
    }
}