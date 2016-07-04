<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Widget;

class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'widget/breadcrumb.phtml';

    protected $containerData = [];
    protected $steps = [];
    protected $selectedStep = NULL;

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