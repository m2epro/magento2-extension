<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\License
 */
class License extends AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;
    /** @var array */
    public $licenseData;
    /** @var array */
    public $locationData;

    /**
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\License $licenseHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->clientHelper = $clientHelper;
        $this->dataHelper = $dataHelper;
        $this->moduleHelper = $moduleHelper;
        $this->licenseHelper = $licenseHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInfoLicense');
        $this->setTemplate('control_panel/info/license.phtml');
    }

    // ----------------------------------------

    protected function _beforeToHtml()
    {
        $this->licenseData = [
            'key'    => $this->dataHelper->escapeHtml($this->licenseHelper->getKey()),
            'domain' => $this->dataHelper->escapeHtml($this->licenseHelper->getDomain()),
            'ip'     => $this->dataHelper->escapeHtml($this->licenseHelper->getIp()),
            'valid'  => [
                'domain' => $this->licenseHelper->isValidDomain(),
                'ip'     => $this->licenseHelper->isValidIp(),
            ],
        ];

        $this->locationData = [
            'domain'             => $this->clientHelper->getDomain(),
            'ip'                 => $this->clientHelper->getIp(),
            'directory'          => $this->clientHelper->getBaseDirectory(),
            'relative_directory' => $this->moduleHelper->getBaseRelativeDirectory(),
        ];

        return parent::_beforeToHtml();
    }
}
