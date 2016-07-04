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

    public function getPageNavigationPath($pathNick, $tabName = NULL, $channel = NULL, $additionalEnd = NULL,
                                          $params = array())
    {
        //todo
        return '';

        $pathParts = array();

        $rootMenuNode = Mage::getConfig()->getNode('adminhtml/menu/m2epro_common');
        $menuLabel = $this->getHelper('View')->getMenuPath($rootMenuNode, $pathNick, $this->getMenuRootNodeLabel());

        if (!$menuLabel) {
            return '';
        }

        $pathParts['menu'] = $menuLabel;

        if ($tabName) {
            $pathParts['tab'] = $this->getHelper('Module\Translation')->__($tabName)
                . ' ' . $this->getHelper('Module\Translation')->__('Tab');
        } else {
            $pathParts['tab'] = NULL;
        }

        $channelLabel = '';
        if ($channel) {

            $components = $this->getActiveComponentsLabels();

            if ($channel == 'any') {
                if (count($components) > 1) {
                    if (!empty($params['any_channel_as_label'])) {
                        $channelLabel = $this->getHelper('Module\Translation')->__('Any Channel');
                    } else {
                        $channelLabel = '[' . join($components, '/') . ']';
                    }
                }

            } elseif ($channel == 'all') {
                if (count($components) > 1) {
                    $channelLabel = $this->getHelper('Module\Translation')->__('All Channels');
                }
            } else {

                if (!$this->getHelper('M2ePro/Component\\' . ucfirst($channel))->isEnabled()) {
                    throw new \Ess\M2ePro\Model\Exception('Channel is not Active!');
                }

                if (count($components) > 1) {
                    $channelLabel = $this->getHelper('Component\\' . ucfirst($channel))->getTitle();
                }
            }
        }

        $pathParts['channel'] = $channelLabel;

        $pathParts['additional'] = $this->getHelper('Module\Translation')->__($additionalEnd);

        $resultPath = array();

        $resultPath['menu'] = $pathParts['menu'];
        if (isset($params['reverse_tab_and_channel']) && $params['reverse_tab_and_channel'] === true) {
            $resultPath['channel'] = $pathParts['channel'];
            $resultPath['tab'] = $pathParts['tab'];
        } else {
            $resultPath['tab'] = $pathParts['tab'];
            $resultPath['channel'] = $pathParts['channel'];
        }
        $resultPath['additional'] = $pathParts['additional'];

        $resultPath = array_diff($resultPath, array(''));

        return join($resultPath, ' > ');
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

    public function getAutocompleteMaxItems()
    {
        $temp = (int)$this->getHelper('Module')->getConfig()
                        ->getGroupValue('/view/amazon/autocomplete/','max_records_quantity');
        return $temp <= 0 ? 100 : $temp;
    }

    //########################################

    public function is3rdPartyShouldBeShown()
    {
        $sessionKey = 'amazon_is_3rd_party_should_be_shown';
        $sessionCache = $this->getHelper('Data\Cache\Session');

        if (!is_null($sessionCache->getValue($sessionKey))) {
            return $sessionCache->getValue($sessionKey);
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

        $sessionCache->setValue($sessionKey, $result);

        return $result;
    }

    //########################################
}