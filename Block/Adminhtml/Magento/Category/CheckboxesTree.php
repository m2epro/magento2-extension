<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Magento\Category;

class CheckboxesTree extends \Magento\Catalog\Block\Adminhtml\Category\Checkboxes\Tree
{
    public function getLoadTreeUrl($expanded = null)
    {
        $params = ['_current' => true, 'id' => null, 'store' => null];
        if ($expanded === null && $this->_backendSession->getIsTreeWasExpanded() || $expanded == true) {
            $params['expand_all'] = true;
        }
        return $this->getUrl('catalog/category/categoriesJson', $params);
    }
}
