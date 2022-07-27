<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

class MoveToListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $this->globalData->setValue(
            'componentMode',
            $this->getRequest()->getParam('componentMode')
        );

        $this->globalData->setValue(
            'accountId',
            $this->getRequest()->getParam('accountId')
        );

        $this->globalData->setValue(
            'marketplaceId',
            $this->getRequest()->getParam('marketplaceId')
        );

        $this->globalData->setValue(
            'ignoreListings',
            $this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('ignoreListings'))
        );

        $movingHandlerJs = 'ListingGridObj.movingHandler';
        if ($this->getRequest()->getParam('componentMode') == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            $movingHandlerJs = 'EbayListingViewSettingsGridObj.movingHandler';
        }

        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid::class,
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
