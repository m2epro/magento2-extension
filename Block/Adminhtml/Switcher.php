<?php

namespace Ess\M2ePro\Block\Adminhtml;

abstract class Switcher extends Magento\AbstractBlock
{
    protected $_template = 'switcher.phtml';

    protected $itemsIds = array();

    protected $paramName = '';

    protected $hasDefaultOption = true;

    //########################################

    abstract public function getLabel();

    abstract public function getItems();

    public function getSwitchUrl()
    {
        $controllerName = $this->getData('controller_name') ? $this->getData('controller_name') : '*';
        return $this->getUrl(
            "*/{$controllerName}/*",
            array('_current' => true, $this->getParamName() => $this->getParamPlaceHolder())
        );
    }

    public function getSwitchCallback()
    {
        $callback = 'switch';
        $callback .= ucfirst($this->paramName);

        return $callback;
    }

    public function getConfirmMessage()
    {
        return '';
    }

    //########################################

    public function getParamName()
    {
        return $this->paramName;
    }

    public function getParamPlaceHolder()
    {
        return '%' . $this->getParamName() . '%';
    }

    public function getSelectedParam()
    {
        return $this->getRequest()->getParam($this->getParamName());
    }

    //########################################

    public function hasDefaultOption()
    {
        return (bool)$this->hasDefaultOption;
    }

    abstract public function getDefaultOptionName();

    public function getDefaultOptionValue()
    {
        return 'all';
    }

    //########################################
}