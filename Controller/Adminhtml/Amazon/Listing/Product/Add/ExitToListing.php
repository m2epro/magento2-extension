<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class ExitToListing extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add\Index
{
    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute(): \Magento\Framework\App\ResponseInterface
    {
        $listingId = $this->getRequest()->getParam('id');
        if ($listingId === null) {
            return $this->_redirect('*/amazon_listing/index');
        }

        $this->cancelProductsAdding();

        return $this->_redirect(
            '*/amazon_listing/view',
            ['id' => $listingId]
        );
    }
}
