<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Wizard\VersionDowngrade;

class Content extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->supportHelper = $supportHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('wizardVersionDowngradeContent');
        $this->setTemplate('wizard/versionDowngrade/content.phtml');
    }

    public function getSupportUrl(): string
    {
        return $this->supportHelper->getDocumentationArticleUrl('help/m2/install-upgrade-m2e-pro');
    }
}
