<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

class Removing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other
{
    public function execute()
    {
        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $productArray = explode(',', $productIds);

        if (empty($productArray)) {
            $this->setAjaxContent('0' , false);
            return $this->getResult();
        }

        foreach ($productArray as $productId) {
            $listingOther = $this->amazonFactory->getObjectLoaded(
                'Listing\Other', $productId
            );

            if (!is_null($listingOther->getProductId())) {
                $listingOther->unmapProduct(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
            }

            /** @var \Ess\M2ePro\Model\Listing\Other\Log $tempLog */
            $tempLog = $this->activeRecordFactory->getObject('Listing\Other\Log');
            $tempLog->setComponentMode($listingOther->getComponentMode());
            $tempLog->addProductMessage(
                $listingOther->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                NULL,
                \Ess\M2ePro\Model\Listing\Other\Log::ACTION_DELETE_ITEM,
                'Item was successfully Deleted',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $listingOther->delete();
        }

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}