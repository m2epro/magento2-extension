<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode
 */
class Mode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionMode');
        // ---------------------------------------
    }

    //########################################

    public function getHelpPageUrl()
    {
        return $this->getHelper('Module\Support')
            ->getDocumentationArticleUrl('x/kgItAQ');
    }

    //########################################
}
