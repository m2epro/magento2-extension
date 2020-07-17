<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\AffectedListingsProducts
 */
class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    private $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function loadCollection(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->getSelect()->where(
            'template_category_id = ? OR template_category_secondary_id = ?',
            $this->model->getId()
        );

        return $collection;
    }

    //########################################
}
