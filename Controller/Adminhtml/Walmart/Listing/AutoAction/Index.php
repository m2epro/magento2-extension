<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
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
        $listing   = $this->walmartFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $this->globalData->setValue('listing', $listing);

        $autoMode = $this->getRequest()->getParam('auto_mode');
        empty($autoMode) && $autoMode = $listing->getAutoMode();

        $autoModes = [
            \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL => 'Walmart_Listing_AutoAction_Mode_GlobalMode',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE => 'Walmart_Listing_AutoAction_Mode_Website',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY => 'Walmart_Listing_AutoAction_Mode_Category',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE => 'Walmart_Listing_AutoAction_Mode'
        ];

        if (isset($autoModes[$autoMode])) {
            $blockName = $autoModes[$autoMode];
        } else {
            $blockName = $autoModes[\Ess\M2ePro\Model\Listing::AUTO_MODE_NONE];
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
