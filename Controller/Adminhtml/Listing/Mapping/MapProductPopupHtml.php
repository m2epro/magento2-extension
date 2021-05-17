<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class  \Ess\M2ePro\Controller\Adminhtml\Listing\Mapping\MapProductPopupHtml
 */
class MapProductPopupHtml extends Listing
{
    //########################################

    public function execute()
    {
        $block = $this->createBlock(
            'Listing_Mapping_View',
            '',
            [
                'data' => [
                    'grid_url' => '*/listing_mapping/mapGrid',
                    'mapping_handler_js' => $this->getMovingHandlerJs(),
                    'mapping_action' => 'remap'
                ]
            ]
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################

    protected function getMovingHandlerJs()
    {
        if ($this->getRequest()->getParam('component_mode') == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return 'EbayListingViewSettingsGridObj.mappingHandler';
        }

        return 'ListingGridObj.mappingHandler';
    }

    //########################################
}
