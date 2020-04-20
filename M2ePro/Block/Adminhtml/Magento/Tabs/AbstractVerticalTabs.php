<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractVerticalTabs
 */
abstract class AbstractVerticalTabs extends AbstractTabs
{
    protected $_template = 'Ess_M2ePro::magento/tabs/vertical.phtml';

    protected $_groups = [];

    public function getGroups()
    {
        return $this->_groups;
    }

    /**
     * Magento method
     * @param string $parentTab
     * @return string
     */
    public function getAccordion($parentTab)
    {
        $html = '';
        foreach ($this->_tabs as $childTab) {
            if ($childTab->getParentTab() === $parentTab->getId()) {
                $html .= $this->getChildBlock('child-tab')->setTab($childTab)->toHtml();
            }
        }
        return $html;
    }
}
