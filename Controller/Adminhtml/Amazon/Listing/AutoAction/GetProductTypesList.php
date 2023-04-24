<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory as ProductTypeCollectionFactory;

class GetProductTypesList extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $productTypeCollectionFactory;

    /**
     * @param ProductTypeCollectionFactory $productTypeCollectionFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->productTypeCollectionFactory = $productTypeCollectionFactory;
    }

    /**
     * @inheridoc
     */
    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', '');

        $collection = $this->productTypeCollectionFactory->create();
        $collection->appendTableDictionary();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'id' => 'id',
            'title' => 'adpt.title',
        ]);

        if ($marketplaceId !== '') {
            $collection->appendFilterMarketplaceId((int)$marketplaceId);
        }

        $productTypeTemplates = $collection->getData();

        $this->setJsonContent($productTypeTemplates);
        return $this->getResult();
    }
}
