<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\Module\Support
 */
class Support extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $urlBuilder;
    protected $modelFactory;

    //########################################

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->modelFactory = $modelFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getPageRoute()
    {
        return 'm2epro/'.$this->getPageControllerName().'/index';
    }

    public function getPageControllerName()
    {
        return 'support';
    }

    //########################################

    public function getWebsiteUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'website_url');
    }

    public function getClientsPortalUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'clients_portal_url');
    }

    public function getHowToGuideUrl($articleUrl)
    {
        $urlParts[] = $this->getSupportUrl()  . '/how-to-guide';

        if ($articleUrl) {
            $urlParts[] = trim($articleUrl, '/');
        }

        return implode('/', $urlParts);
    }

    public function getSupportUrl($urlPart = null)
    {
        $urlParts[] = trim(
            $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'support_url'),
            '/'
        );

        if ($urlPart) {
            $urlParts[] = trim($urlPart, '/');
        }

        return implode('/', $urlParts);
    }

    public function getMagentoMarketplaceUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'magento_marketplace_url');
    }

    //########################################

    public function getDocumentationUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'documentation_url');
    }

    public function getDocumentationComponentUrl($component)
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

    public function getDocumentationArticleUrl($tinyLink)
    {
        return $this->getDocumentationUrl() . $tinyLink;
    }

    //----------------------------------------

    public function getKnowledgebaseUrl($articleUrl = null)
    {
        $urlParts[] = $this->getSupportUrl('knowledgebase');

        if ($articleUrl) {
            $urlParts[] = trim($articleUrl, '/');
        }

        return implode('/', $urlParts);
    }

    public function getKnowledgebaseComponentUrl($component)
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

    public function getKnowledgebaseArticleUrl($articleLink)
    {
        return $this->getKnowledgebaseUrl() . trim($articleLink, '/') . '/';
    }

    //----------------------------------------

    public function getIdeasUrl()
    {
        return $this->getSupportUrl('ideas');
    }

    public function getIdeasComponentUrl($component)
    {
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->getIdeasUrl() . 'ebay/';
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->getIdeasUrl() . 'amazon/';
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->getIdeasUrl() . 'category/1563595-walmart-integration/';
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
        }
    }

    public function getIdeasArticleUrl($articleLink)
    {
        return $this->getIdeasUrl() . trim($articleLink, '/') . '/';
    }

    //----------------------------------------

    public function getForumUrl()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'forum_url');
    }

    public function getForumComponentUrl($component)
    {
        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->getForumUrl() . 'ebay/';
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->getForumUrl() . 'amazon/';
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->getForumUrl() . 'forum/21-walmart-integration/';
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
        }
    }

    public function getForumArticleUrl($articleLink)
    {
        return $this->getForumUrl() . trim($articleLink, '/') . '/';
    }

    //########################################

    public function getContactEmail()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue('/support/', 'contact_email');
    }

    //########################################
}
