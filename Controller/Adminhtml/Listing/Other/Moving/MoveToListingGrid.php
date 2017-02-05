<?php

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

use Ess\M2ePro\Controller\Adminhtml\Listing;

class MoveToListingGrid extends Listing
{
    public function execute()
    {
        $this->getHelper('Data\GlobalData')->setValue(
            'componentMode', $this->getRequest()->getParam('componentMode')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'accountId', $this->getRequest()->getParam('accountId')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'marketplaceId', $this->getRequest()->getParam('marketplaceId')
        );
        $this->getHelper('Data\GlobalData')->setValue(
            'ignoreListings', $this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('ignoreListings'))
        );

        $component = ucfirst(strtolower($this->getRequest()->getParam('componentMode')));
        $movingHandlerJs = $component.'ListingOtherGridObj.movingHandler';

        $block = $this->getLayout()->createBlock(
            'Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid','',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/listing_other_moving/moveToListingGrid',array('_current'=>true)
                ),
                'moving_handler_js' => $movingHandlerJs,
            ]]
        );

        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}