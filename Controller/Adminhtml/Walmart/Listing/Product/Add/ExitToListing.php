<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class ExitToListing extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add\Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $listingId = $this->getRequest()->getParam('id');
        if ($listingId === null) {
            return $this->_redirect('*/walmart_listing/index');
        }

        $additionalData = $this->getListing()->getSettings('additional_data');
        $this->cancelProductsAdding($additionalData);
        $this->clear();

        return $this->_redirect(
            '*/walmart_listing/view',
            ['id' => $listingId]
        );
    }
}
