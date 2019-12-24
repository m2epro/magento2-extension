<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\InstallationEbay\Breadcrumb
 */
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
