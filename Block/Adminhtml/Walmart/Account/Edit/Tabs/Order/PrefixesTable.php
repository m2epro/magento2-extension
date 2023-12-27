<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account\Edit\Tabs\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PrefixesTable extends AbstractBlock
{
    protected $_template = 'walmart/account/order/prefixesTable.phtml';
    /** @var array */
    private $formData = [];
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->magentoHelper = $magentoHelper;
    }

    public function setFormData(array $formData = []): self
    {
        $this->formData = $formData;

        return $this;
    }

    public function getSampleMagentoOrderId(): string
    {
        return $this->magentoHelper->getNextMagentoOrderId();
    }

    public function getMagentoOrderPrefix(): string
    {
        if (empty($this->formData['magento_orders_settings']['number']['prefix']['prefix'])) {
            return '';
        }

        return $this->escapeHtml($this->formData['magento_orders_settings']['number']['prefix']['prefix']);
    }

    public function getMagentoOrderPrefixWFS(): string
    {
        if (empty($this->formData['magento_orders_settings']['number']['prefix']['wfs-prefix'])) {
            return '';
        }

        return $this->escapeHtml($this->formData['magento_orders_settings']['number']['prefix']['wfs-prefix']);
    }
}
