<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
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

    public function execute()
    {
        $listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );
        $this->helperDataGlobalData->setValue('listing', $listing);

        $autoMode  = $this->getRequest()->getParam('auto_mode');
        empty($autoMode) && $autoMode = $listing->getAutoMode();

        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\GlobalMode::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Website::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category::class;
                break;
            default:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode::class;
                break;
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->getLayout()->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
