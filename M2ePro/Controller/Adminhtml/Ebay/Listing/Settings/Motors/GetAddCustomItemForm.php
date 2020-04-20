<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\GetAddCustomItemForm
 */
class GetAddCustomItemForm extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\Add\Item\CustomForm $block */
        $block = $this->createBlock('Ebay_Listing_View_Settings_Motors_Add_Item_CustomForm');

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}
