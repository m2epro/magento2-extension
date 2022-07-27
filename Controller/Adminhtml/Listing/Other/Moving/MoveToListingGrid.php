<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

class MoveToListingGrid extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->globalData = $globalData;
        $this->dataHelper = $dataHelper;
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
            $this->dataHelper->jsonDecode($this->getRequest()->getParam('ignoreListings'))
        );

        $component = ucfirst(strtolower($this->getRequest()->getParam('componentMode')));
        $movingHandlerJs = $component.'ListingOtherGridObj.movingHandler';

        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\Moving\Grid::class,
            '',
            ['data' => [
                'grid_url' => $this->getUrl(
                    '*/listing_other_moving/moveToListingGrid',
                    ['_current'=>true]
                ),
                'moving_handler_js' => $movingHandlerJs,
            ]]
        );

        $this->setAjaxContent($block->toHtml());
        return $this->getResult();
    }
}
