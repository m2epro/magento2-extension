<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationAmazon;

class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    public function _construct()
    {
        parent::_construct();

        $this->setSteps([
            [
                'id' => 'registration',
                'title' => $this->__('Step 1'),
                'description' => $this->__('Module Registration'),
            ],
            [
                'id' => 'account',
                'title' => $this->__('Step 2'),
                'description' => $this->__('Account Onboarding'),
            ],
            [
                'id' => 'listingTutorial',
                'title' => $this->__('Step 3'),
                'description' => $this->__('First Listing Creation'),
            ],
        ]);
    }
}