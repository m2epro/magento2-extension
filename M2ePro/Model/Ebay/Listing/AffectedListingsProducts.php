<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing;

use Ess\M2ePro\Model\Ebay\Template\Manager;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\AffectedListingsProducts
 */
class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProducts\AbstractModel
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayParentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayParentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayParentFactory = $ebayParentFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    public function getObjects(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $this->model->getId());

        if (isset($filters['template'])) {
            /** @var Manager $templateManager */
            $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
            $templateManager->setTemplate($filters['template']);

            $collection->addFieldToFilter(
                $templateManager->getModeColumnName(),
                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
            );
        }

        return $collection->getItems();
    }

    public function getObjectsData($columns = '*', array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $this->model->getId());

        if (isset($filters['template'])) {
            /** @var Manager $templateManager */
            $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
            $templateManager->setTemplate($filters['template']);

            $collection->addFieldToFilter(
                $templateManager->getModeColumnName(),
                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
            );
        }

        if (is_array($columns) && !empty($columns)) {
            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns($columns);
        }

        return $collection->getItems();
    }

    public function getIds(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $this->model->getId());

        if (isset($filters['template'])) {
            /** @var Manager $templateManager */
            $templateManager = $this->modelFactory->getObject('Ebay_Template_Manager');
            $templateManager->setTemplate($filters['template']);

            $collection->addFieldToFilter(
                $templateManager->getModeColumnName(),
                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
            );
        }

        return $collection->getAllIds();
    }

    //########################################
}
