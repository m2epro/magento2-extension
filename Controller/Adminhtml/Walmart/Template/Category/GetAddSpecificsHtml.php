<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add;
use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class GetAddSpecificsHtml extends Category
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Category\Categories\Specific\Add $addBlock */
        $addBlock = $this->getLayout()->createBlock(Add::class);

        $gridBlock = $this->prepareGridBlock();
        $addBlock->setChild('specifics_grid', $gridBlock);

        $this->setAjaxContent($addBlock->toHtml());
        return $this->getResult();
    }
}
