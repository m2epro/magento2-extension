<?php

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel;

abstract class Command extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    //########################################

    public function execute()
    {
        if (!($action = $this->getRequest()->getParam('action'))) {
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageInspectionTabUrl());
        }

        $methodName = $action.'Action';

        if (!method_exists($this, $methodName)) {
            return $this->_redirect($this->getHelper('View\ControlPanel')->getPageInspectionTabUrl());
        }

        $actionResult = $this->$methodName();

        if (is_string($actionResult)) {
            $this->getRawResult()->setContents($actionResult);
            return $this->getRawResult();
        }

        return $actionResult;
    }

    protected function _validateSecretKey()
    {
        return true;
    }

    //########################################

    /**
     * It will allow to use control panel features even if extension is disabled, etc.
     * @param \Magento\Framework\App\RequestInterface $request
     * @return bool
     */
    protected function preDispatch(\Magento\Framework\App\RequestInterface $request)
    {
        return true;
    }

    //########################################

    protected function getStyleHtml()
    {
        return <<<HTML
<style type="text/css">

    table.grid {
        border-color: black;
        border-style: solid;
        border-width: 1px 0 0 1px;
    }
    table.grid th {
        padding: 5px 20px;
        border-color: black;
        border-style: solid;
        border-width: 0 1px 1px 0;
        background-color: silver;
        color: white;
        font-weight: bold;
    }
    table.grid td {
        padding: 3px 10px;
        border-color: black;
        border-style: solid;
        border-width: 0 1px 1px 0;
    }

</style>
HTML;
    }

    //########################################
}