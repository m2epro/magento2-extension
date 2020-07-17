<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

/**
 * Class \Ess\M2ePro\Helper\View\Walmart
 */
class Walmart extends \Ess\M2ePro\Helper\AbstractHelper
{
    const NICK  = 'walmart';

    const WIZARD_INSTALLATION_NICK = 'installationWalmart';
    const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::walmart';

    protected $walmartFactory;
    protected $urlBuilder;
    protected $activeRecordFactory;
    protected $authSession;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->urlBuilder = $urlBuilder;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->authSession = $authSession;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Walmart Integration');
    }

    //########################################

    public function getMenuRootNodeLabel()
    {
        return $this->getTitle();
    }

    //########################################

    public function getWizardInstallationNick()
    {
        return self::WIZARD_INSTALLATION_NICK;
    }

    public function isInstallationWizardFinished()
    {
        return $this->getHelper('Module\Wizard')->isFinished(
            $this->getWizardInstallationNick()
        );
    }

    //########################################

    public function isResetFilterShouldBeShown($key, $id)
    {
        $sessionKey = "is_reset_filter_should_be_shown_{$key}_" . (int)$id;

        $sessionCache = $this->getHelper('Data_Cache_Runtime');
        if ($sessionCache->getValue($sessionKey) !== null) {
            return $sessionCache->getValue($sessionKey);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter($key, $id)
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED)
            ->addFieldToFilter('is_online_price_invalid', 0);

        return $sessionCache->setValue($sessionKey, (bool)$collection->getSize());
    }

    //########################################
}
