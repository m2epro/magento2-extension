<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving\MoveToListingGrid
 */
class MoveToListingGrid extends Listing
{
    public function execute()
    {
        $this->getHelper('Data\GlobalData')->setValue(
            'componentMode',
            $this->getRequest()->getParam('componentMode')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'accountId',
            $this->getRequest()->getParam('accountId')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'marketplaceId',
            $this->getRequest()->getParam('marketplaceId')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'ignoreListings',
            $this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('ignoreListings'))
        );

        $movingHandlerJs = 'ListingGridObj.movingHandler';
        if ($this->getRequest()->getParam('componentMode') == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $movingHandlerJs = 'EbayListingViewSettingsGridObj.movingHandler';
        }

        $block = $this->createBlock(
            'Listing_Moving_Grid',
            '',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/listing_moving/moveToListingGrid',
                    ['_current'=>true]
                ),
                'moving_handler_js' => $movingHandlerJs,
            ]]
        );

        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}
