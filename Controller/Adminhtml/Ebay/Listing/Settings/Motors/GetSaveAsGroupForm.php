<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\GetSaveAsGroupForm
 */
class GetSaveAsGroupForm extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Form $block */
        $block = $this->getLayout()
                  ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Group\Form::class);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}
