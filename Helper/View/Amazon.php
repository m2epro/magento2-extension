<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Amazon extends \Ess\M2ePro\Helper\AbstractHelper
{
    // M2ePro_TRANSLATIONS
    // Sell On Multi-Channels

    const NICK  = 'amazon';

    const WIZARD_INSTALLATION_NICK = 'installationAmazon';
    const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::amazon';

    protected $urlBuilder;
    protected $activeRecordFactory;
    protected $authSession;

    //########################################

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->authSession = $authSession;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getTitle()
    {
        return $this->getHelper('Module\Translation')->__('Amazon Integration (Beta)');
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

    public function is3rdPartyShouldBeShown()
    {
        $runtimeCache = $this->getHelper('Data\Cache\Runtime');

        if (!is_null($runtimeCache->getValue(__METHOD__))) {
            return $runtimeCache->getValue(__METHOD__);
        }

        $accountCollection = $this->activeRecordFactory->getObject('Amazon\Account')->getCollection();
        $accountCollection->addFieldToFilter(
            'other_listings_synchronization', \Ess\M2ePro\Model\Amazon\Account::OTHER_LISTINGS_SYNCHRONIZATION_YES
        );

        if ((bool)$accountCollection->getSize()) {
            $result = true;
        } else {
            $collection = $this->activeRecordFactory->getObject('Amazon\Listing\Other')->getCollection();

            $logCollection = $this->activeRecordFactory->getObject('Listing\Other\Log')->getCollection();
            $logCollection->addFieldToFilter(
                'component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK
            );

            $result = $collection->getSize() || $logCollection->getSize();
        }

        $runtimeCache->setValue(__METHOD__, $result);

        return $result;
    }

    //########################################
}