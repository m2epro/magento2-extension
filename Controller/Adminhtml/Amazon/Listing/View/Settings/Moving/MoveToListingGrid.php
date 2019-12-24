<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\View\Settings\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\View\Settings\Moving\MoveToListingGrid
 */
class MoveToListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    //########################################

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
            'Listing_Moving_Grid',
            '',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/amazon_listing_view_settings_moving/moveToListingGrid',
                    ['_current'=>true]
                ),
                'moving_handler_js' => 'ListingGridHandlerObj.movingHandler',
            ]]
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
