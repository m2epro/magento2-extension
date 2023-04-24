<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

abstract class Add extends Main
{
    /** @var string */
    protected $sessionKey = 'amazon_listing_product_add';
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Variation */
    protected $variationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Variation $variationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->variationHelper = $variationHelper;
    }

    //########################################

    protected function setSessionValue($key, $value)
    {
        $sessionData = $this->getSessionValue();
        $sessionData[$key] = $value;

        $this->getHelper('Data\Session')->setValue($this->sessionKey, $sessionData);

        return $this;
    }

    protected function getSessionValue($key = null)
    {
        $sessionData = $this->getHelper('Data\Session')->getValue($this->sessionKey);

        if ($sessionData === null) {
            $sessionData = [];
        }

        if ($key === null) {
            return $sessionData;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }

    // ---------------------------------------

    protected function clearSession()
    {
        $this->getHelper('Data\Session')->setValue($this->sessionKey, null);
    }

    //########################################

    protected function filterProductsForNewAsin($productsIds)
    {
        return $this->variationHelper->filterProductsNotMatchingForNewAsin($productsIds);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getListing()
    {
        if ($this->listing === null) {
            $this->listing = $this->amazonFactory->getObjectLoaded('Listing', $this->getRequest()->getParam('id'));
        }

        return $this->listing;
    }

    //########################################

    protected function setProductTypeTemplate($productsIds, $templateId)
    {
        $connWrite = $this->resourceConnection->getConnection();
        $tableAmazonListingProduct = $this->activeRecordFactory
            ->getObject('Amazon_Listing_Product')
            ->getResource()
            ->getMainTable();

        $productsIds = array_chunk($productsIds, 1000);
        foreach ($productsIds as $productsIdsChunk) {
            $connWrite->update($tableAmazonListingProduct, [
                'template_product_type_id' => $templateId,
            ], '`listing_product_id` IN (' . implode(',', $productsIdsChunk) . ')');
        }
    }

    //########################################

    protected function setWizardStep($step)
    {
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK, $step);
    }

    protected function endWizard()
    {
        /** @var \Ess\M2ePro\Helper\Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        if (!$wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            return;
        }

        $wizardHelper->setStatus(
            \Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK,
            \Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED
        );

        $this->getHelper('Magento')->clearMenuCache();
    }

    /**
     * @param array $listingProductsIds
     *
     * @return void
     */
    protected function deleteListingProducts(array $listingProductsIds)
    {
        foreach ($listingProductsIds as $listingProductId) {
            try {
                $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
                $listingProduct->delete();
            } catch (\Exception $e) {
            }
        }

        $listing = $this->getListing();
        $listing->setSetting('additional_data', 'adding_listing_products_ids', []);
        $listing->setSetting('additional_data', 'adding_new_asin_listing_products_ids', []);
        $listing->setSetting('additional_data', 'auto_search_was_performed', 0);
        $listing->setSetting('additional_data', 'adding_new_asin_product_type_data', []);
        $listing->save();
    }
}
