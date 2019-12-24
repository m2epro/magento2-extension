<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

/**
 * Class \Ess\M2ePro\Model\Wizard\InstallationWalmart
 */
class InstallationWalmart extends Wizard
{
    protected $steps = [
        'registration',
        'account',
        'settings',
        'listingTutorial'
    ];

    //########################################

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK;
    }

    //########################################
}
