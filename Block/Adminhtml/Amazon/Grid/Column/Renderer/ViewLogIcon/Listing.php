<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\ViewLogIcon;

use Ess\M2ePro\Model\Listing\Log;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\ViewLogIcon\Listing
 */
class Listing extends \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Listing
{
    //########################################

    protected function getAvailableActions()
    {
        $translator = $this->getHelper('Module\Translation');

        return parent::getAvailableActions() +
            [
                Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT => $translator->__('Remove from Channel'),
                Log::ACTION_DELETE_AND_REMOVE_PRODUCT     => $translator->__('Remove from Channel & Listing'),
                Log::ACTION_DELETE_PRODUCT_FROM_LISTING   => $translator->__('Remove from Listing'),
                Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT    => $translator->__('Switch to AFN'),
                Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT    => $translator->__('Switch to MFN')
            ];
    }

    //########################################

    protected function getLastActions($listingProductId, $logs)
    {
        $summary = $this->createBlock('Amazon_Listing_Log_Grid_LastActions')->setData([
            'entity_id'           => $listingProductId,
            'logs'                => $logs,
            'available_actions'   => $this->getAvailableActions(),
            'is_variation_parent' => $this->isVariationParent(),
            'view_help_handler'   => "{$this->getJsHandler()}.viewItemHelp",
            'hide_help_handler'   => "{$this->getJsHandler()}.hideItemHelp"
        ]);

        return $summary->toHtml();
    }

    //########################################
}
