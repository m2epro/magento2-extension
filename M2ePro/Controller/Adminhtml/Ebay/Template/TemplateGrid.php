<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Template\TemplateGrid
 */
class TemplateGrid extends Template
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Grid $switcherBlock */
        $grid = $this->createBlock(
            'Ebay_Template_Grid'
        );

        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }

    //########################################
}
