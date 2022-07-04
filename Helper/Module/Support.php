<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Support
{
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
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
     * @param string $articleUrl
     *
     * @return string
     */
    public function getHowToGuideUrl(string $articleUrl): string
    {
        $urlParts[] = $this->getSupportUrl() . '/how-to-guide';

        if ($articleUrl) {
            $urlParts[] = trim($articleUrl, '/');
        }

        return implode('/', $urlParts);
    }

    /**
     * @param string $urlPart
     *
     * @return string
     */
    public function getSupportUrl(string $urlPart = ''): string
    {
        $baseSupportUrl = $this->config->getGroupValue('/support/', 'support_url');

        $urlParts[] = trim($baseSupportUrl, '/');

        if ($urlPart !== '') {
            $urlParts[] = trim($urlPart, '/');
        }

        return implode('/', $urlParts);
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
     * @param string $articleUrl
     *
     * @return string
     */
    public function getKnowledgebaseUrl(string $articleUrl = ''): string
    {
        $urlParts[] = $this->getSupportUrl('knowledgebase');

        if ($articleUrl !== '') {
            $urlParts[] = trim($articleUrl, '/');
        }

        return implode('/', $urlParts);
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
                return $this->getKnowledgebaseUrl() . 'ebay/';
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->getKnowledgebaseUrl() . 'amazon/';
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->getKnowledgebaseUrl() . 'category/1561695-walmart-integration/';
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
        }
    }

    /**
     * @param string $articleLink
     *
     * @return string
     */
    public function getKnowledgebaseArticleUrl(string $articleLink): string
    {
        return $this->getKnowledgebaseUrl() . trim($articleLink, '/') . '/';
    }

    /**
     * @return mixed|null
     */
    public function getContactEmail()
    {
        return $this->config->getGroupValue('/support/', 'contact_email');
    }
}
