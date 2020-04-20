<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Widget;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
 */
class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'widget/breadcrumb.phtml';

    protected $containerData = [];
    protected $steps = [];
    protected $selectedStep = null;

    //########################################

    public function setContainerData(array $data)
    {
        $this->containerData = $data;
        return $this;
    }

    public function getContainerData($key)
    {
        return isset($this->containerData[$key]) ? $this->containerData[$key] : '';
    }

    public function getSteps()
    {
        return $this->steps;
    }

    public function setSteps(array $steps)
    {
        $this->steps = $steps;
        return $this;
    }

    public function getSelectedStep()
    {
        return $this->selectedStep;
    }

    public function setSelectedStep($stepId)
    {
        $this->selectedStep = $stepId;
        return $this;
    }

    //########################################
}
