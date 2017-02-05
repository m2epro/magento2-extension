<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\Moving;

class MoveToListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
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

        $block = $this->createBlock(
            'Ebay\Listing\Moving\Grid','',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/ebay_listing_other_moving/moveToListingGrid',array('_current'=>true)
                ),
                'moving_handler_js' => 'EbayListingOtherGridObj.movingHandler',
            ]]
        );

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}