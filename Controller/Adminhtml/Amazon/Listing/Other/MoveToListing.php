<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;
use Ess\M2ePro\Helper\Component\Amazon as ComponentAmazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Other\MoveToListing
 */
class MoveToListing extends Main
{

    public function execute()
    {
        $sessionHelper = $this->getHelper('Data\Session');
        $sessionKey = ComponentAmazon::NICK . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;
        $selectedProducts = $sessionHelper->getValue($sessionKey);

        /** @var \Ess\M2ePro\Model\Listing $listingInstance */
        $listingInstance = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            (int)$this->getRequest()->getParam('listingId')
        );

        $errorsCount = 0;
        foreach ($selectedProducts as $otherListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->amazonFactory->getObjectLoaded('Listing\Other', $otherListingProduct);

            $listingProduct = $listingInstance->getChildObject()->addProductFromOther(
                $listingOther,
                \Ess\M2ePro\Helper\Data::INITIATOR_USER
            );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $errorsCount++;
                continue;
            }

            $listingOther->moveToListingSucceed();
        }

        $sessionHelper->removeValue($sessionKey);

        if ($errorsCount) {
            if (count($selectedProducts) == $errorsCount) {
                $this->setJsonContent(
                    [
                        'result'  => false,
                        'message' => $this->__(
                            'Products were not moved because they already exist in the selected Listing.'
                        )
                    ]
                );
                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result'   => true,
                    'isFailed' => true,
                    'message'  => $this->__(
                        'Some products were not moved because they already exist in the selected Listing.'
                    )
                ]
            );
        } else {
            $this->setJsonContent(
                [
                    'result'   => true,
                    'message'  => $this->__('Product(s) was Moved.')
                ]
            );
        }

        return $this->getResult();
    }
}
