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

        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\GlobalMode::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Website::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category::class;
                break;
            default:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode::class;
                break;
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->getLayout()->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
