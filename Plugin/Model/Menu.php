<?php

namespace Ess\M2ePro\Plugin\Model;

class Menu
{
    protected $maintenanceHelper;
    protected $cachePermanent;
    protected $pageConfig;
    protected $ebayView;
    protected $amazonView;
    protected $wizardHelper;
    protected $magentoHelper;
    protected $magentoConfig;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Maintenance\Setup $maintenanceHelper,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Ess\M2ePro\Helper\View\Ebay $ebayView,
        \Ess\M2ePro\Helper\View\Amazon $amazonView,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Magento\Config\Model\Config $magentoConfig
    )
    {
        $this->maintenanceHelper = $maintenanceHelper;
        $this->cachePermanent = $cachePermanent;
        $this->pageConfig = $pageConfig;
        $this->ebayView = $ebayView;
        $this->amazonView = $amazonView;
        $this->wizardHelper = $wizardHelper;
        $this->magentoHelper = $magentoHelper;
        $this->magentoConfig = $magentoConfig;
    }

    public function aroundGetMenu($subject, \Closure $proceed)
    {
        /** @var \Magento\Backend\Model\Menu $menuModel */
        $menuModel = $proceed();

        $ebayMenu = $menuModel->get(\Ess\M2ePro\Helper\View\Ebay::MENU_ROOT_NODE_NICK);
        $amazonMenu = $menuModel->get(\Ess\M2ePro\Helper\View\Amazon::MENU_ROOT_NODE_NICK);

        if ($this->maintenanceHelper->isEnabled()) {
            $ebayMenu->getChildren()->exchangeArray([]);
            $amazonMenu->getChildren()->exchangeArray([]);
            $this->magentoHelper->clearMenuCache();
            return $menuModel;
        }

        $this->removeOtherListingsMenuItem();

        $ebayActiveBlocker = $this->wizardHelper->getActiveBlockerWizard(\Ess\M2ePro\Helper\View\Ebay::NICK);

        if ($ebayActiveBlocker) {
            $ebayMenu->getChildren()->exchangeArray([]);
            $ebayMenu->setAction('m2epro/wizard_' . $this->wizardHelper->getNick($ebayActiveBlocker));
        }

        $amazonActiveBlocker = $this->wizardHelper->getActiveBlockerWizard(\Ess\M2ePro\Helper\View\Amazon::NICK);

        if ($amazonActiveBlocker) {
            $amazonMenu->getChildren()->exchangeArray([]);
            $amazonMenu->setAction('m2epro/wizard_' . $this->wizardHelper->getNick($amazonActiveBlocker));
        }

        $globalActiveBlocker = $this->wizardHelper->getActiveBlockerWizard('*');

        if ($globalActiveBlocker && $globalActiveBlocker->getNick() == 'migrationFromMagento1') {
            $ebayMenu->setAction($ebayMenu->getAction() . '/index/referrer/' . \Ess\M2ePro\Helper\View\Ebay::NICK);
            $amazonMenu->setAction(
                $amazonMenu->getAction() . '/index/referrer/' . \Ess\M2ePro\Helper\View\Amazon::NICK
            );
        }

        //todo implement new clear menu cache logic
        if ($ebayActiveBlocker || $amazonActiveBlocker) {
            $this->magentoHelper->clearMenuCache();
        }

        return $menuModel;
    }

    private function removeOtherListingsMenuItem()
    {
        $needToCleanMenuCache = false;

        if (!$this->ebayView->is3rdPartyShouldBeShown()) {
            $this->pageConfig->addPageAsset('Ess_M2ePro::css/menu/ebay_listing_other.css');
        }

        $isEbay3rdPartyShouldBeShownKey = 'ebay_3rd_party_should_be_shown';
        $isEbay3rdPartyShouldBeShownLastState = $this->cachePermanent->getValue($isEbay3rdPartyShouldBeShownKey);

        if (
            is_null($isEbay3rdPartyShouldBeShownLastState)
            || $isEbay3rdPartyShouldBeShownLastState != $this->ebayView->is3rdPartyShouldBeShown()
        ) {
            $needToCleanMenuCache = true;
            $this->cachePermanent->setValue(
                $isEbay3rdPartyShouldBeShownKey, $this->ebayView->is3rdPartyShouldBeShown()
            );
        }

        //---------------------------------------------

        $isAmazon3rdPartyShouldBeShownKey = 'amazon_3rd_party_should_be_shown';
        $isAmazon3rdPartyShouldBeShownLastState = $this->cachePermanent->getValue($isAmazon3rdPartyShouldBeShownKey);

        if (
            is_null($isAmazon3rdPartyShouldBeShownLastState)
            || $isAmazon3rdPartyShouldBeShownLastState != $this->amazonView->is3rdPartyShouldBeShown()
        ) {
            $needToCleanMenuCache = true;
            $this->cachePermanent->setValue(
                $isAmazon3rdPartyShouldBeShownKey, $this->amazonView->is3rdPartyShouldBeShown()
            );
        }

        if (!$this->amazonView->is3rdPartyShouldBeShown()) {
            $this->pageConfig->addPageAsset('Ess_M2ePro::css/menu/amazon_listing_other.css');
        }

        if ($needToCleanMenuCache) {
            $this->magentoHelper->clearMenuCache();
        }
    }
}