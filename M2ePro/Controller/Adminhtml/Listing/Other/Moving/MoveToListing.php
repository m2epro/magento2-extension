<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving\MoveToListing
 */
class MoveToListing extends Listing
{
    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');

        $selectedProducts = (array)$this->getHelper('Data')->jsonDecode(
            $this->getRequest()->getParam('selectedProducts')
        );
        $listingId = (int)$this->getRequest()->getParam('listingId');

        $listingInstance = $this->parentFactory->getCachedObjectLoaded(
            $componentMode,
            'Listing',
            $listingId
        );

        $listingLogModel = $this->activeRecordFactory->getObject('Listing\Log');
        $listingLogModel->setComponentMode($componentMode);

        $errors = 0;
        foreach ($selectedProducts as $otherListingProduct) {
            $otherListingProductInstance = $this->parentFactory
                ->getObjectLoaded($componentMode, 'Listing\Other', $otherListingProduct);

            $listingProductInstance = $listingInstance
                ->getChildObject()
                ->addProductFromOther(
                    $otherListingProductInstance,
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    false,
                    false
                );

            if (!($listingProductInstance instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $errors++;
                continue;
            }

            $listingLogModel->addProductMessage(
                $listingId,
                $otherListingProductInstance->getProductId(),
                $listingProductInstance->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                null,
                \Ess\M2ePro\Model\Listing\Log::ACTION_MOVE_FROM_OTHER_LISTING,
                'Item was successfully Moved',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
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
