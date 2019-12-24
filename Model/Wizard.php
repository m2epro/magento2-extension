<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\Wizard
 */
class Wizard extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    protected $steps = [];

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Wizard');
    }

    //########################################

    /**
     * @return bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * @return null
     */
    public function getNick()
    {
        return null;
    }

    //########################################

    /**
     * @return array
     */
    public function getSteps()
    {
        return $this->steps;
    }

    public function getFirstStep()
    {
        return reset($this->steps);
    }

    // ---------------------------------------

    public function getPrevStep()
    {
        $currentStep = $this->getHelper('Module\Wizard')->getStep($this->getNick());
        $prevStepIndex = array_search($currentStep, $this->steps) - 1;
        return isset($this->steps[$prevStepIndex]) ? $this->steps[$prevStepIndex] : false;
    }

    public function getNextStep()
    {
        $currentStep = $this->getHelper('Module\Wizard')->getStep($this->getNick());
        $nextStepIndex = array_search($currentStep, $this->steps) + 1;
        return isset($this->steps[$nextStepIndex]) ? $this->steps[$nextStepIndex] : false;
    }

    //########################################
}
