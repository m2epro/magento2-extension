<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

class GetAutoCategoryFormHtml extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
    }

    public function execute()
    {
        $listing = $this->walmartFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );
        $this->globalData->setValue('walmart_listing', $listing);

        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category\Form::class);

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}
