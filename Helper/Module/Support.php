<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Support extends \Ess\M2ePro\Helper\AbstractHelper
{
    const TYPE_BRONZE  = 'bronze';
    const TYPE_SILVER  = 'silver';
    const TYPE_GOLD    = 'gold';

    protected $urlBuilder;
    protected $modelFactory;
    protected $moduleConfig;
    protected $cacheConfig;

    //########################################
    
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->modelFactory = $modelFactory;
        $this->moduleConfig = $moduleConfig;
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getPageUrl(array $params = array())
    {
        return $this->urlBuilder->getUrl($this->getPageRoute(), $params);
    }

    public function getPageRoute()
    {
        return 'm2epro/'.$this->getPageControllerName().'/index';
    }

    public function getPageControllerName()
    {
        return 'support';
    }

    //########################################

    public function getDocumentationUrl($component = NULL, $articleUrl = NULL, $tinyLink = NULL)
    {
        $urlParts[] = $this->moduleConfig->getGroupValue('/support/', 'documentation_url');

        if ($component || $articleUrl) {
            $urlParts[] = 'display';
        }

        if ($component) {
            if ($component == \Ess\M2ePro\Helper\Component\Ebay::NICK) {
                $urlParts[] = 'eBayMagento2';
            } elseif ($component == \Ess\M2ePro\Helper\Component\Amazon::NICK) {
                $urlParts[] = 'AmazonMagento2';
            } else {
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid Channel.');
            }
        }

        if ($articleUrl) {
            $urlParts[] = trim($articleUrl, '/');
        }

        if ($tinyLink) {
            $urlParts[] = $tinyLink;
        }

        return implode('/', $urlParts);
    }

    public function getKnowledgeBaseUrl($articleUrl = NULL)
    {
        $urlParts[] = $this->moduleConfig->getGroupValue('/support/', 'knowledge_base_url');

        if ($articleUrl) {
            $urlParts[] = trim($articleUrl, '/');
        }

        return implode('/', $urlParts);
    }

    public function getVideoTutorialsUrl($component)
    {
        return $this->getDocumentationUrl($component,'Video+Tutorials');
    }

    //########################################

    public function getMainWebsiteUrl()
    {
        return $this->moduleConfig->getGroupValue('/support/', 'main_website_url');
    }

    public function getClientsPortalBaseUrl()
    {
        return $this->moduleConfig->getGroupValue('/support/', 'clients_portal_url');
    }

    public function getCommunityBaseUrl()
    {
        return $this->moduleConfig->getGroupValue('/support/', 'community');
    }

    public function getIdeasBaseUrl()
    {
        return $this->moduleConfig->getGroupValue('/support/', 'ideas');
    }

    // ---------------------------------------

    public function getMainSupportUrl($urlPart = null)
    {
        $urlParts[] = trim(
            $this->moduleConfig->getGroupValue('/support/', 'main_support_url'),
            '/'
        );

        if ($urlPart) {
            $urlParts[] = trim($urlPart, '/');
        }

        return implode('/', $urlParts);
    }

    public function getMagentoConnectUrl()
    {
        return $this->moduleConfig->getGroupValue('/support/', 'magento_connect_url');
    }

    //########################################

    public function getContactEmail()
    {
        $email = $this->moduleConfig->getGroupValue('/support/', 'contact_email');

        try {

            /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('settings','get','supportEmail');
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            if (!empty($response['email'])) {
                $email = $response['email'];
            }

        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }

        return $email;
    }

    public function getType()
    {
        $type = $this->cacheConfig->getGroupValue('/support/premium/','type');
        $lastUpdateDate = $this->cacheConfig->getGroupValue('/support/premium/','last_update_time');

        if ($type && strtotime($lastUpdateDate) + 3600*24 > $this->getHelper('Data')->getCurrentGmtDate(true)) {
            return $type;
        }

        $type = self::TYPE_BRONZE;

        try {

            /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('settings','get','supportType');
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            !empty($response['type']) && $type = $response['type'];

        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
        }

        $this->cacheConfig->setGroupValue('/support/premium/','type',$type);
        $this->cacheConfig->setGroupValue('/support/premium/','last_update_time',
            $this->getHelper('Data')->getCurrentGmtDate());

        return $type;
    }

    // ---------------------------------------

    public function isTypePremium()
    {
        return $this->getType() == self::TYPE_GOLD || $this->getType() == self::TYPE_SILVER;
    }

    //########################################
}