<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Unmanaged;

class MoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    protected $sessionDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->sessionDataHelper = $sessionDataHelper;
    }

    public function execute()
    {
        $sessionKey = \Ess\M2ePro\Helper\Component\Amazon::NICK . '_'
            . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;
        $selectedProducts = $this->sessionDataHelper->getValue($sessionKey);

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

        $this->sessionDataHelper->removeValue($sessionKey);

        if ($errorsCount) {
            if (count($selectedProducts) == $errorsCount) {
                $this->setJsonContent(
                    [
                        'result' => false,
                        'message' => __(
                            'Products were not moved because they already exist in the selected Listing or do not
                            belong to the channel account or marketplace of the listing.'
                        ),
                    ]
                );

                return $this->getResult();
            }

            $this->setJsonContent(
                [
                    'result' => true,
                    'isFailed' => true,
                    'message' => __(
                        'Some products were not moved because they already exist in the selected Listing or do not
                        belong to the channel account or marketplace of the listing.'
                    ),
                ]
            );
        } else {
            $this->setJsonContent(
                [
                    'result' => true,
                    'message' => __('Product(s) was Moved.'),
                ]
            );
        }

        return $this->getResult();
    }
}
