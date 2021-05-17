<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class  \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping\MapProductPopupHtml
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
                    'grid_url' => '*/listing_other_mapping/mapGrid',
                    'mapping_handler_js' => 'ListingOtherMappingObj',
                    'mapping_action' => 'map'
                ]
            ]
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
