<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\UniqueMessageFilter
 */
class UniqueMessageFilter extends AbstractContainer
{
    protected $_template = 'log/uniqueMessageFilter.phtml';

    //########################################

    public function getParamName()
    {
        return 'only_unique_messages';
    }

    public function getFilterUrl()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = [];
        } else {
            $params = $this->getRequest()->getParams();
        }

        $tabId = null;

        if ($tabId !== null) {
            $params['tab'] = $tabId;
        }

        if ($this->isChecked()) {
            $params[$this->getParamName()] = 0;
        } else {
            $params[$this->getParamName()] = 1;
        }

        return $this->getUrl($this->getData('route'), $params);
    }

    public function isChecked()
    {
        return $this->getRequest()->getParam($this->getParamName(), true);
    }

    //########################################
}
