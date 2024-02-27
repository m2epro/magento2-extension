<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class ExitToListing extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings
{
    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $listingId = $this->getRequest()->getParam('id');
        if ($listingId === null) {
            return $this->_redirect('*/ebay_listing/index');
        }

        $this->cancelProductsAdding();

        if ((int)$this->getRequest()->getParam('step') === 1) {
            return $this->_redirect('*/ebay_listing_product_add/', [
                'id' => $listingId,
                '_current' => true,
                'step' => 1,
                'back' => null
            ]);
        }

        return $this->_redirect(
            '*/ebay_listing/view',
            ['id' => $listingId]
        );
    }
}
