<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

/**
 * Class \Ess\M2ePro\Model\Wizard\InstallationAmazon
 */
class InstallationAmazon extends Wizard
{
    protected $steps = [
        'registration',
        'account',

        'listingTutorial',
        'listingGeneral',
        'listingSelling',
        'listingSearch',

        'sourceMode',
        'productSelection',
        'searchAsin',
        'newAsin'
    ];

    //########################################

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK;
    }

    //########################################
}
