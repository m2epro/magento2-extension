<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat\GetTaxCodesGrid
 */
class GetTaxCodesGrid extends Template
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\TaxCodes\Grid $grid */
        $grid = $this->createBlock('Walmart_Template_SellingFormat_TaxCodes_Grid');
        $grid->setNoSelection($this->getRequest()->getParam('no_selection'));
        $grid->setMarketplaceId($this->getRequest()->getParam('marketplace_id'));

        $this->setAjaxContent($grid);

        return $this->getResult();
    }

    //########################################
}
