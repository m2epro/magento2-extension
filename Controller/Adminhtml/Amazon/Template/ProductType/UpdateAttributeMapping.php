<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class UpdateAttributeMapping extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\ManagerFactory */
    private $attributeMappingManagerFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType */
    private $productTypeResource;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping\ManagerFactory $attributeMappingManagerFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType $productTypeResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->attributeMappingManagerFactory = $attributeMappingManagerFactory;
        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeResource = $productTypeResource;
    }

    public function execute()
    {
        $productTypeId = (int)$this->getRequest()->getParam('product_type_id');

        $productType = $this->productTypeFactory->create();
        $this->productTypeResource->load($productType, $productTypeId);

        if ($productType->getId() === null) {
            $this->setJsonContent([
                'status' => false,
                'message' => __('Incorrect Product Type id'),
            ]);

            return $this->getResult();
        }

        $attributeMappingManager = $this->attributeMappingManagerFactory->create($productType);
        $attributeMappingManager->updateMappings();

        $this->setJsonContent([
            'status' => true,
        ]);

        return $this->getResult();
    }
}
