<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration;

use Ess\M2ePro\Controller\Adminhtml\ControlPanel\Command;
use Ess\M2ePro\Helper\Component\Walmart as WalmartHelper;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\ControlPanel\Module\Integration\Walmart
 */
class Walmart extends Command
{
    private $synchConfig;
    private $formKey;
    private $csvParser;
    private $phpEnvironmentRequest;
    private $productFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Synchronization $synchConfig,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\File\Csv $csvParser,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        Context $context
    ) {
        $this->synchConfig = $synchConfig;
        $this->formKey = $formKey;
        $this->csvParser = $csvParser;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->productFactory = $productFactory;
        parent::__construct($context);
    }

    //########################################

    /**
     * @title "Reset 3rd Party"
     * @description "Clear all 3rd party items for all Accounts"
     */
    public function resetOtherListingsAction()
    {
        $listingOther = $this->parentFactory->getObject(WalmartHelper::NICK, 'Listing\Other');
        $walmartListingOther = $this->activeRecordFactory->getObject('Walmart_Listing_Other');

        $stmt = $listingOther->getResourceCollection()->getSelect()->query();

        $SKUs = [];
        foreach ($stmt as $row) {
            $listingOther->setData($row);
            $walmartListingOther->setData($row);

            $listingOther->setChildObject($walmartListingOther);
            $walmartListingOther->setParentObject($listingOther);
            $SKUs[] = $walmartListingOther->getSku();

            $listingOther->delete();
        }

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_walmart_item');
        foreach (array_chunk($SKUs, 1000) as $chunkSKUs) {
            $this->resourceConnection->getConnection()->delete($tableName, ['sku IN (?)' => $chunkSKUs]);
        }

        $accountsCollection = $this->parentFactory->getObject(WalmartHelper::NICK, 'Account')->getCollection();

        foreach ($accountsCollection->getItems() as $account) {
            $additionalData = (array)$this->getHelper('Data')
                ->jsonDecode($account->getAdditionalData());

            unset($additionalData['last_listing_products_synchronization']);

            $account->setSettings('additional_data', $additionalData)->save();
        }

        $this->getMessageManager()->addSuccess('Successfully removed.');
        $this->_redirect($this->getHelper('View\ControlPanel')->getPageModuleTabUrl());
    }

    //########################################
}
