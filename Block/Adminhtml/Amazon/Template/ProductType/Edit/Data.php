<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit;

class Data extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var string */
    protected $_template = 'template/2_column.phtml';
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType $productType */
    private $productType;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->productType = $productType;
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType\Edit\Data
     * @throws \Magento\Framework\Exception\LocalizedException
     */
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
