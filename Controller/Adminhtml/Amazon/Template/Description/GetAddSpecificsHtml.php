<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description\GetAddSpecificsHtml
 */
class GetAddSpecificsHtml extends Description
{
    //########################################

    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Category\Specific\Add $addBlock */
        $addBlock = $this->createBlock('Amazon_Template_Description_Category_Specific_Add');

        $gridBlock = $this->prepareGridBlock();
        $addBlock->setChild('specifics_grid', $gridBlock);

        $this->setAjaxContent($addBlock->toHtml());
        return $this->getResult();
    }

    //########################################
}
