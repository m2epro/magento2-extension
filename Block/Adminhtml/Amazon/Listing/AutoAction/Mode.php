<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction;

class Mode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\AbstractMode
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /**
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param array $data
     */
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

    /**
     * @return string
     */
    public function getHelpPageUrl(): string
    {
        return $this->supportHelper->getDocumentationArticleUrl('x/uAMVB');
    }
}
