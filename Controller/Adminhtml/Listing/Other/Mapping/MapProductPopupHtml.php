<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

class MapProductPopupHtml extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    public function execute()
    {
        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\Mapping\View::class,
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
}
