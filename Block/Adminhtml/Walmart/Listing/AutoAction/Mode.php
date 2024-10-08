<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction;

class Mode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\AbstractMode
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;

    public function __construct(
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        parent::__construct($formFactory, $globalDataHelper, $magentoStoreHelper, $context, $data);
        $this->supportHelper = $supportHelper;
    }

    public function getHelpPageUrl()
    {
        return $this->supportHelper->getDocumentationArticleUrl('adding-products-automatically-auto-addremove-rules');
    }
}
