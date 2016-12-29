<?php

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Synchronization;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class Content extends AbstractBlock
{
    protected $_template = 'wizard/migrationFromMagento1/installation/synchronization.phtml';

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(<<<HTML
All the data from the database has been imported and prepared for its working in the M2E Pro for Magento v2.x and
now to move forward, you will need to synchronize the Marketplaces data.<br/>
<b>Please note</b> that the process might be rather time- and resource-consuming and might take up to 30 minutes.
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