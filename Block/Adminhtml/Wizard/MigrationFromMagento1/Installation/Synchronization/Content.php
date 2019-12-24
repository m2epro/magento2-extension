<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Synchronization;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Wizard\MigrationFromMagento1\Installation\Synchronization\Content
 */
class Content extends AbstractBlock
{
    protected $_template = 'wizard/migrationFromMagento1/installation/synchronization.phtml';

    protected function _prepareLayout()
    {
        $this->getLayout()->getBlock('wizard.help.block')->setContent(<<<HTML
At this step, the marketplace data will be synchronized automatically. Once the process is completed,
you can proceed to the 4th Migration Wizard step.<br/>
<b>Note</b>: The step is rather time- and resource-consuming and can take up to 30 minutes.
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

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function getWalmartMarketplaces()
    {
        $amazonMarketplaces = [];

        /** @var \Ess\M2ePro\Model\Marketplace[] $marketplaces */
        $marketplaces = $this->getHelper('Data\GlobalData')->getValue('marketplaces');

        foreach ($marketplaces as $marketplace) {
            if ($marketplace->isComponentModeWalmart()) {
                $amazonMarketplaces[] = $marketplace;
            }
        }

        return $amazonMarketplaces;
    }
}
