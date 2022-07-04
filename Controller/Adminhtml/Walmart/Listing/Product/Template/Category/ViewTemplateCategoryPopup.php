<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Template\Category;

class ViewTemplateCategoryPopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Template\Category::class)
        );

        return $this->getResult();
    }
}
