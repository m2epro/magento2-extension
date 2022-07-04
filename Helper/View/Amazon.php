<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Amazon
{
    public const NICK = 'amazon';

    public const WIZARD_INSTALLATION_NICK = 'installationAmazon';
    public const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::amazon';

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizard;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Ess\M2ePro\Helper\Module\Wizard $wizard
    ) {
        $this->translation = $translation;
        $this->wizard = $wizard;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->translation->__('Amazon Integration');
    }

    /**
     * @return string
     */
    public function getMenuRootNodeLabel(): string
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getWizardInstallationNick(): string
    {
        return self::WIZARD_INSTALLATION_NICK;
    }

    /**
     * @return bool
     */
    public function isInstallationWizardFinished(): bool
    {
        return $this->wizard->isFinished(
            $this->getWizardInstallationNick()
        );
    }
}
