<?php

namespace Ess\M2ePro\Block\Adminhtml\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class NotCreatedFilter extends AbstractContainer
{
    protected $_template = 'order/not_created_filter.phtml';

    public function getParamName()
    {
        return 'not_created_only';
    }

    public function getFilterUrl()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $params = array();
        } else {
            $params = $this->getRequest()->getParams();
        }

        $tabId = null;

        if (!is_null($tabId)) {
            $params['tab'] = $tabId;
        }

        if ($this->isChecked()) {
            unset($params[$this->getParamName()]);
        } else {
            $params[$this->getParamName()] = true;
        }

        return $this->getUrl('*/'.$this->getData('controller').'/*', $params);
    }

    public function isChecked()
    {
        return $this->getRequest()->getParam($this->getParamName());
    }
}