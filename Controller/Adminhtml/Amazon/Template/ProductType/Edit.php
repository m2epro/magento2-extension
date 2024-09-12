<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class Edit extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dataHelper = $dataHelper;
        $this->productTypeFactory = $productTypeFactory;
        $this->registryManager = $registryManager;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $productType = $this->productTypeFactory->createEmpty();

        if ($id !== null) {
            $productType = $this->templateProductTypeRepository->get((int)$id);
        }

        $this->addContent(
            $this->getLayout()
                 ->createBlock(
                     \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit::class,
                     '',
                     ['productType' => $productType]
                 )
        );

        $block = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate\Popup::class);

        $registryKey = "/amazon/product_type/validation/validate_by_id/$id/";
        if ($this->registryManager->getValue($registryKey)) {
            $this->registryManager->deleteValue($registryKey);
            $block->setData(
                'validate_product_type_function',
                "ProductTypeValidatorPopup.openForProductType($id);"
            );
        }

        $this->addContent($block);

        if ($productType->getId()) {
            $headerText = __("Edit Product Type Settings");
            $headerText .= ' "' . $this->dataHelper->escapeHtml($productType->getTitle()) . '"';
        } else {
            $headerText = __("Add Product Type Settings");
        }

        $this->getResultPage()
             ->getConfig()
             ->getTitle()->prepend($headerText);
        $this->setPageHelpLink('amazon-product-type');

        return $this->getResultPage();
    }
}
