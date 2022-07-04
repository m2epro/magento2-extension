<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\GetAutoCategoryFormHtml
 */
class GetAutoCategoryFormHtml extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperDataGlobalData = $helperDataGlobalData;
    }

    //########################################

    public function execute()
    {
        $listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );
        $this->helperDataGlobalData->setValue('amazon_listing', $listing);

        $block = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category\Form::class);

        $this->setAjaxContent($block);
        return $this->getResult();
    }

    //########################################
}
