<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\SellingFormat;

use Ess\M2ePro\Model\Ebay\Template\Manager;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\SellingFormat\AffectedListingsProducts
 */
class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProducts\AbstractModel
{
    //########################################

    public function getObjects(array $filters = [])
    {
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $templateManager->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT,
            $this->model->getId(),
            false
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING,
            $this->model->getId(),
            false
        );

        foreach ($listings as $listing) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\AffectedListingsProducts $listingAffectedProducts */
            $listingAffectedProducts = $this->modelFactory->getObject('Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getObjects(
                ['template' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT]
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function getObjectsData($columns = '*', array $filters = [])
    {
        /** @var Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $templateManager->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT,
            $this->model->getId(),
            true,
            $columns
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING,
            $this->model->getId(),
            false
        );

        foreach ($listings as $listing) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\AffectedListingsProducts $listingAffectedProducts */
            $listingAffectedProducts = $this->modelFactory->getObject('Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getObjectsData(
                $columns,
                ['template' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT]
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function getIds(array $filters = [])
    {
        /** @var Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $templateManager->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT,
            $this->model->getId(),
            true,
            ['id']
        );

        $listings = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING,
            $this->model->getId(),
            false
        );

        foreach ($listings as $listing) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\AffectedListingsProducts $listingAffectedProducts */
            $listingAffectedProducts = $this->modelFactory->getObject('Ebay_Listing_AffectedListingsProducts');
            $listingAffectedProducts->setModel($listing);

            $tempListingsProducts = $listingAffectedProducts->getObjectsData(
                ['id'],
                ['template' => \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT]
            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return array_keys($listingsProducts);
    }

    //########################################
}
