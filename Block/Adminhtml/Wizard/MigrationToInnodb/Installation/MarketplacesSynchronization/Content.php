<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb\Installation\MarketplacesSynchronization;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class  \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationToInnodb\Installation\MarketplacesSynchronization\Content
 */
class Content extends AbstractBlock
{
    protected $enabledMarketplaces = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('wizardInstallationMarketplacesSynchronization');
        $this->setTemplate('wizard/migrationToInnodb/installation/marketplacesSynchronization.phtml');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(
            $this->__(
                <<<HTML
Click <b>Continue</b> to synchronize the Marketplaces enabled in your Account configuration.
HTML
            )
        );

        parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $collection = $this->activeRecordFactory->getObject('Marketplace')->getCollection();
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);

        foreach ($collection->getItems() as $marketplace) {
            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            if (!$marketplace->getResource()->isDictionaryExist($marketplace)) {
                $component = (string)$this->getHelper('Component')->getComponentTitle($marketplace->getComponentMode());
                $this->enabledMarketplaces[$component][] = $marketplace;
            }
        }

        return parent::_beforeToHtml();
    }

    //########################################

    public function getEnabledMarketplaces()
    {
        return $this->enabledMarketplaces;
    }

    //########################################
}
