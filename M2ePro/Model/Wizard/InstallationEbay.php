<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

/**
 * Class \Ess\M2ePro\Model\Wizard\InstallationEbay
 */
class InstallationEbay extends Wizard
{
    protected $steps = [
        'registration',
        'account',

        'listingTutorial',
        'listingAccount',
        'listingGeneral',
        'listingSelling',
        'listingSynchronization',

        'sourceMode',
        'productSelection',
        'productSettings',

        'categoryStepOne',
        'categoryStepTwo',
        'categoryStepThree',
    ];

    //########################################

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getHelper('Component\Ebay')->isEnabled();
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Helper\View\Ebay::WIZARD_INSTALLATION_NICK;
    }

    //########################################
}
