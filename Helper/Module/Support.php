<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Support
{
    /** @var \Magento\Backend\Model\UrlInterface */
    protected $urlBuilder;
    /** @var \Ess\M2ePro\Model\Factory */
    protected $modelFactory;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->modelFactory = $modelFactory;
        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
        $this->clientHelper = $clientHelper;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getPageRoute(): string
    {
        return 'm2epro/' . $this->getPageControllerName() . '/index';
    }

    /**
     * @return string
     */
    public function getPageControllerName(): string
    {
        return 'support';
    }

    /**
     * @return mixed|null
     */
    public function getWebsiteUrl()
    {
        return $this->config->getGroupValue('/support/', 'website_url');
    }

    /**
     * @return mixed|null
     */
    public function getClientsPortalUrl()
    {
        return $this->config->getGroupValue('/support/', 'clients_portal_url');
    }

    /**
     * @param string $urlPart
     *
     * @return string
     */
    public function getSupportUrl(string $urlPart): string
    {
        $baseSupportUrl = $this->config->getGroupValue('/support/', 'support_url');

        return rtrim($baseSupportUrl, '/') . '/' . ltrim($urlPart, '/');
    }

    /**
     * @return mixed|null
     */
    public function getMagentoMarketplaceUrl()
    {
        return $this->config->getGroupValue('/support/', 'magento_marketplace_url');
    }

    /**
     * @return mixed|null
     */
    public function getDocumentationUrl()
    {
        return $this->config->getGroupValue('/support/', 'documentation_url');
    }

    /**
     * @param string $component
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDocumentationComponentUrl(string $component): string
    {
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->getDocumentationUrl() . '/display/eBayMagento2/';
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->getDocumentationUrl() . 'display/AmazonMagento2/';
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->getDocumentationUrl() . 'display/WalmartMagento2/';
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
        }
    }

    /**
     * @param string $tinyLink
     *
     * @return string
     */
    public function getDocumentationArticleUrl(string $tinyLink): string
    {
        return $this->getDocumentationUrl() . $tinyLink;
    }

    /**
     * @param string $component
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getKnowledgebaseComponentUrl(string $component): string
    {
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->getSupportUrl('/support/solutions/folders/9000194666');
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->getSupportUrl('/support/solutions/folders/9000194663');
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->getSupportUrl('/support/solutions/folders/9000194662');
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
        }
    }

    /**
     * @return mixed|null
     */
    public function getContactEmail()
    {
        return $this->config->getGroupValue('/support/', 'contact_email');
    }

    public function getYoutubeChannelUrl()
    {
        return 'https://www.youtube.com/c/M2Epro-Magento-Amazon-eBay-Walmart';
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getSummaryInfo(): string
    {
        return <<<DATA
----- MAIN INFO -----
{$this->getMainInfo()}

---- LOCATION INFO ----
{$this->getLocationInfo()}

----- PHP INFO -----
{$this->getPhpInfo()}
DATA;
    }

    /**
     * @return string
     */
    public function getMainInfo(): string
    {
        $platformInfo = [
            'name'    => $this->magentoHelper->getName(),
            'edition' => $this->magentoHelper->getEditionName(),
            'version' => $this->magentoHelper->getVersion()
        ];

        $extensionInfo = [
            'name'    => $this->moduleHelper->getName(),
            'version' => $this->moduleHelper->getPublicVersion()
        ];

        return <<<INFO
Platform: {$platformInfo['name']} {$platformInfo['edition']} {$platformInfo['version']}
---------------------------
Extension: {$extensionInfo['name']} {$extensionInfo['version']}
---------------------------
INFO;
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getLocationInfo(): string
    {
        $locationInfo = [
            'domain' => $this->clientHelper->getDomain(),
            'ip' => $this->clientHelper->getIp(),
        ];

        return <<<INFO
Domain: {$locationInfo['domain']}
---------------------------
Ip: {$locationInfo['ip']}
---------------------------
INFO;
    }

    /**
     * @return string
     */
    public function getPhpInfo(): string
    {
        $phpInfo = $this->clientHelper->getPhpSettings();
        $phpInfo['api'] = $this->clientHelper->getPhpApiName();
        $phpInfo['version'] = $this->clientHelper->getPhpVersion();

        return <<<INFO
Version: {$phpInfo['version']}
---------------------------
Api: {$phpInfo['api']}
---------------------------
Memory Limit: {$phpInfo['memory_limit']}
---------------------------
Max Execution Time: {$phpInfo['max_execution_time']}
---------------------------
INFO;
    }
}
