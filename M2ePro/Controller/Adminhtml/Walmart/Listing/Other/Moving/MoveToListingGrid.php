<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other\Moving\MoveToListingGrid
 */
class MoveToListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Other
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

        $block = $this->createBlock(
            'Walmart_Listing_Moving_Grid',
            '',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/walmart_listing_other_moving/moveToListingGrid',
                    ['_current'=>true]
                ),
                'moving_handler_js' => 'WalmartListingOtherGridObj.movingHandler',
            ]]
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}
