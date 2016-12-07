<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Maintenance;

class Debug extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAINTENANCE_COOKIE_KEY = 'm2epro_debug_maintenance';
    const MAINTENANCE_COOKIE_DURATION = 3600;

    protected $modelFactory;
    protected $moduleConfig;
    protected $cookieMetadataFactory;
    protected $cookieManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->moduleConfig = $moduleConfig;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/debug/maintenance/', 'mode');
    }

    public function isOwner()
    {
        return (bool)$this->cookieManager->getCookie(self::MAINTENANCE_COOKIE_KEY);
    }

    //########################################

    public function enable()
    {
        $this->moduleConfig->setGroupValue('/debug/maintenance/', 'mode', 1);

        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $restoreDate = $this->getHelper('Data')->getDate($currentTimeStamp + self::MAINTENANCE_COOKIE_DURATION);
        $this->moduleConfig->setGroupValue('/debug/maintenance/', 'restore_date', $restoreDate);

        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setHttpOnly(true)
            ->setPath('/')
            ->setSecure($this->_getRequest()->isSecure())
            ->setDuration(60*60*24);

        $this->cookieManager->setPublicCookie(self::MAINTENANCE_COOKIE_KEY, 'true', $cookieMetadata);
    }

    public function disable()
    {
        $this->moduleConfig->setGroupValue('/debug/maintenance/', 'mode', 0);
        $this->moduleConfig->setGroupValue('/debug/maintenance/', 'restore_date', null);

        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setHttpOnly(true)
            ->setPath('/')
            ->setSecure($this->_getRequest()->isSecure());

        $this->cookieManager->deleteCookie(self::MAINTENANCE_COOKIE_KEY, $cookieMetadata);
    }

    //########################################

    public function isExpired()
    {
        $restoreDate = $this->moduleConfig->getGroupValue(
            '/debug/maintenance/', 'restore_date'
        );

        if (!$restoreDate) {
            return true;
        }

        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

        if ($currentTimeStamp >= strtotime($restoreDate)) {
            return true;
        }

        return false;
    }

    public function prolongRestoreDate()
    {
        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $restoreDate = $this->getHelper('Data')->getDate($currentTimeStamp + self::MAINTENANCE_COOKIE_DURATION);
        $this->moduleConfig->setGroupValue('/debug/maintenance/', 'restore_date', $restoreDate);
    }

    //########################################
}