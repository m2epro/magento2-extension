<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

class InstallationEbay extends Wizard
{
    /** @var string[] */
    protected $steps = [
        'registration',
        'account',
        'settings',

        'listingTutorial',
        'listingGeneral',

    ];

    /**
     * @return bool
     */
    public function isActive($view)
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
}
