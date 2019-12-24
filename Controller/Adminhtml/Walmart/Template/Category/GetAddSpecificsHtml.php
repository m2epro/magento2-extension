<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\GetAddSpecificsHtml
 */
class GetAddSpecificsHtml extends Category
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add $addBlock */
        $addBlock = $this->createBlock('Walmart_Template_Category_Categories_Specific_Add');

        $gridBlock = $this->prepareGridBlock();
        $addBlock->setChild('specifics_grid', $gridBlock);

        $this->setAjaxContent($addBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}
