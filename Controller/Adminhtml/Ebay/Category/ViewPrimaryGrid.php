<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

class ViewPrimaryGrid extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    public function execute()
    {
        $this->setRuleModel();
        $this->setAjaxContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\ProductsPrimary\Grid::class)
        );

        return $this->getResult();
    }
}
