<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Traits;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId
 */
class ProductId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Number
{
    use Traits\BlockTrait;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;

    /** @var \Ess\M2ePro\Helper\Factory  */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->modelFactory        = $modelFactory;
        $this->helperFactory       = $helperFactory;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    //########################################

    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $this->_getValue($row);

        if ($productId === null) {
            return $this->__('N/A');
        }

        if ($this->getColumn()->getData('store_id') !== null) {
            $storeId = (int)$this->getColumn()->getData('store_id');
        } elseif ($row->getData('store_id') !== null) {
            $storeId = (int)$row->getData('store_id');
        } else {
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $url = $this->getUrl('catalog/product/edit', ['id' => $productId, 'store' => $storeId]);
        $withoutImageHtml = '<a href="' . $url . '" target="_blank">' . $productId . '</a>';

        if (!$this->moduleConfiguration->getViewShowProductsThumbnailsMode()) {
            return $withoutImageHtml;
        }

        /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $thumbnail = $magentoProduct->getThumbnailImage();
        if ($thumbnail === null) {
            return $withoutImageHtml;
        }

        return <<<HTML
<a href="{$url}" target="_blank">
    {$productId}
    <div style="margin-top: 5px"><img style="max-width: 100px; max-height: 100px;" src="{$thumbnail->getUrl()}" /></div>
</a>
HTML;
    }

    //########################################
}
