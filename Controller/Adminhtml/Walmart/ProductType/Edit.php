<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->productTypeFactory = $productTypeFactory;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $productTypeId = $this->getRequest()->getParam('id');
        $productType = !empty($productTypeId)
            ? $this->productTypeRepository->get((int)$productTypeId)
            : $this->productTypeFactory->createEmpty();

        $this->addContent(
            $this
                ->getLayout()
                ->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit::class,
                    '',
                    ['productType' => $productType]
                )
        );

        $title = $productType->isObjectNew()
            ? __('Add Product Type Settings')
            : __('Edit Product Type Settings "%title"', ['title' => $productType->getTitle()]);

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend((string)$title);

        $this->setPageHelpLink('walmart-product-types');

        return $this->getResultPage();
    }
}
