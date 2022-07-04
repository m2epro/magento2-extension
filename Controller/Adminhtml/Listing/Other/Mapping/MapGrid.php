<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping\MapGrid
 */
class MapGrid extends Listing
{
    public function execute()
    {
        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\Mapping\Grid::class,
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
