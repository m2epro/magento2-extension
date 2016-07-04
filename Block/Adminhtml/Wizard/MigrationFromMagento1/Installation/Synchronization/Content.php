<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Synchronization;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Content extends AbstractBlock
{
    protected $_template = 'wizard/migrationFromMagento1/installation/synchronization.phtml';

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(<<<HTML
The Marketplace Data must be resynchronized to complete M2E Pro migration from Magento v 1.x to Magento v 2.x.<br/><br/>
Below you can find the list of the Marketplaces which were enabled in M2E Pro based on the Magento v 1.x.
They will be automatically resynchronized after pressing <strong>Continue</strong> button.<br/><br/><strong>
Please note</strong> that the process might be rather time- and resource-consuming and may take up to 30 minutes.
HTML
);

        parent::_prepareLayout();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function getEbayMarketplaces()
    {
        $ebayMarketplaces = [];

        /** @var \Ess\M2ePro\Model\Marketplace[] $marketplaces */
        $marketplaces = $this->getHelper('Data\GlobalData')->getValue('marketplaces');

        foreach ($marketplaces as $marketplace) {
            if ($marketplace->isComponentModeEbay()) {
                $ebayMarketplaces[] = $marketplace;
            }
        }

        return $ebayMarketplaces;
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function getAmazonMarketplaces()
    {
        $amazonMarketplaces = [];

        /** @var \Ess\M2ePro\Model\Marketplace[] $marketplaces */
        $marketplaces = $this->getHelper('Data\GlobalData')->getValue('marketplaces');

        foreach ($marketplaces as $marketplace) {
            if ($marketplace->isComponentModeAmazon()) {
                $amazonMarketplaces[] = $marketplace;
            }
        }

        return $amazonMarketplaces;
    }
}