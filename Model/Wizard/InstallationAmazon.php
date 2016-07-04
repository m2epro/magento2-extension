<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

class InstallationAmazon extends Wizard
{
    protected $steps = array(
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
    );

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