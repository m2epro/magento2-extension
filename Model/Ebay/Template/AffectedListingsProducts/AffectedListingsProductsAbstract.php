<?php


/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\AffectedListingsProductsAbstract
 */
abstract class AffectedListingsProductsAbstract extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    abstract public function getTemplateNick();

    //########################################

    public function loadCollection(array $filters = [])
    {
        $ids = [];

        /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager */
        $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
        $templateManager->setTemplate($this->getTemplateNick());

        $tempListingsProducts = $templateManager->getAffectedOwnerObjects(
            \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT,
            $this->model->getId(),
            true,
            ['id']
        );

        foreach ($tempListingsProducts as $tempListingsProduct) {
            if (!isset($listingsProductsIds[$tempListingsProduct['id']])) {
                $ids[$tempListingsProduct['id']] = $tempListingsProduct['id'];
            }
        }

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
                ['template' => $this->getTemplateNick()]
            );

            foreach ($tempListingsProducts as $tempListingsProduct) {
                if (!isset($listingsProductsIds[$tempListingsProduct['id']])) {
                    $ids[$tempListingsProduct['id']] = $tempListingsProduct['id'];
                }
            }
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel $collection */
        $listingProductCollection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', ['in' => $ids]);

        return $listingProductCollection;
    }

    //########################################
}
