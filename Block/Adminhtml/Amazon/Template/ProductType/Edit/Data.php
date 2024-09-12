<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $_template = 'template/2_column.phtml';
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
    private $productType;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->productType = $productType;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout(): Data
    {
        $this->setChild(
            'tabs',
            $this->getLayout()
                ->createBlock(
                    \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Tabs::class,
                    '',
                    ['productType' => $this->productType]
                )
        );
        return parent::_prepareLayout();
    }
}
