<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Main;
use Ess\M2ePro\Helper\Component\Ebay as ComponentEbay;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\SourceMode as SourceModeBlock;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\MoveToListing
 */
class MoveToListing extends Main
{
    public function execute()
    {
        $sessionHelper = $this->getHelper('Data\Session');
        $sessionKey = ComponentEbay::NICK . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;
        $selectedProducts = $sessionHelper->getValue($sessionKey);

        /** @var \Ess\M2ePro\Model\Listing $listingInstance */
        $listingInstance = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            (int)$this->getRequest()->getParam('listingId')
        );

        $errorsCount = 0;
        $tempProducts = [];
        foreach ($selectedProducts as $otherListingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $this->ebayFactory->getObjectLoaded('Listing\Other', $otherListingProduct);
            $listingProduct = $listingInstance->getChildObject()
                ->addProductFromOther(
                    $listingOther,
                    \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                    false,
                    false
                );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $errorsCount++;
                continue;
            }

            $tempProducts[] = $listingProduct->getId();
        }

        $addingProducts = array_unique(
            array_merge(
                $tempProducts,
                $listingInstance->getChildObject()->getAddedListingProductsIds()
            )
        );

        $listingInstance->getChildObject()->setData(
            'product_add_ids',
            $this->getHelper('Data')->jsonEncode($addingProducts)
        );
        $listingInstance->setSetting('additional_data', 'source', SourceModeBlock::MODE_OTHER);
        $listingInstance->getChildObject()->save();
        $listingInstance->save();

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
            $this->messageManager->addSuccess($this->__('Product(s) was successfully Moved.'));
            $this->setJsonContent(['result' => true]);
        }

        return $this->getResult();
    }
}
