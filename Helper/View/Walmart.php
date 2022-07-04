<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Walmart
{
    public const NICK = 'walmart';

    public const WIZARD_INSTALLATION_NICK = 'installationWalmart';
    public const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::walmart';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizard;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Runtime */
    private $runtimeCache;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Ess\M2ePro\Helper\Module\Wizard $wizard,
        \Ess\M2ePro\Helper\Data\Cache\Runtime $runtimeCache
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->translation = $translation;
        $this->wizard = $wizard;
        $this->runtimeCache = $runtimeCache;
    }

    // ----------------------------------------

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->translation->__('Walmart Integration');
    }

    /**
     * @return string
     */
    public function getMenuRootNodeLabel(): string
    {
        return $this->getTitle();
    }

    // ----------------------------------------

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

    // ----------------------------------------

    public function isResetFilterShouldBeShown($key, $id)
    {
        $sessionKey = "is_reset_filter_should_be_shown_{$key}_" . (int)$id;

        $sessionCache = $this->runtimeCache;
        if ($sessionCache->getValue($sessionKey) !== null) {
            return $sessionCache->getValue($sessionKey);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter($key, $id)
                   ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED)
                   ->addFieldToFilter('is_online_price_invalid', 0);

        $sessionCache->setValue($sessionKey, (bool)$collection->getSize());

        return (bool)$collection->getSize();
    }
}
