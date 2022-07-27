<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );
        $this->getHelper('Data\GlobalData')->setValue('listing', $listing);

        $autoMode = $this->getRequest()->getParam('auto_mode');
        empty($autoMode) && $autoMode = $listing->getAutoMode();

        switch ($autoMode) {
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\GlobalMode::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Website::class;
                break;
            case \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category::class;
                break;
            default:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode::class;
                break;
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->getLayout()->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
