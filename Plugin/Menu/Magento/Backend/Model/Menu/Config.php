<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Menu\Magento\Backend\Model\Menu;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Helper\View\Amazon;
use Ess\M2ePro\Helper\View\Ebay;
use Ess\M2ePro\Helper\Module\Maintenance\General as Maintenance;

class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    const MENU_STATE_REGISTRY_KEY = '/menu/state/';
    const MAINTENANCE_MENU_STATE_CACHE_KEY = 'maintenance_menu_state';

    protected $activeRecordFactory;
    protected $pageConfig;
    protected $itemFactory;

    protected $isProcessed = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Backend\Model\Menu\Item\Factory $itemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->pageConfig = $pageConfig;
        $this->itemFactory = $itemFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function canExecute()
    {
        return true;
    }

    //########################################

    public function aroundGetMenu(\Magento\Backend\Model\Menu\Config $interceptor, \Closure $callback)
    {
        return $this->execute('getMenu', $interceptor, $callback);
    }

    // ---------------------------------------

    protected function processGetMenu(\Magento\Backend\Model\Menu\Config $interceptor, \Closure $callback)
    {
        /** @var \Magento\Backend\Model\Menu $menuModel */
        $menuModel = $callback();

        if ($this->isProcessed) {
            return $menuModel;
        }

        $this->isProcessed = true;

        $maintenanceMenuState = $this->helperFactory->getObject('Data\Cache\Permanent')->getValue(
            self::MAINTENANCE_MENU_STATE_CACHE_KEY
        );

        if ($this->helperFactory->getObject('Module\Maintenance\General')->isEnabled()) {
            if (is_null($maintenanceMenuState)) {
                $this->helperFactory->getObject('Data\Cache\Permanent')->setValue(
                    self::MAINTENANCE_MENU_STATE_CACHE_KEY, true
                );
                $this->helperFactory->getObject('Magento')->clearMenuCache();
            }
            return $this->processMaintenance($menuModel);
        } elseif(!is_null($maintenanceMenuState)) {
            $this->helperFactory->getObject('Data\Cache\Permanent')->removeValue(
                self::MAINTENANCE_MENU_STATE_CACHE_KEY
            );
            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }

        $this->processMenuCacheClearing();

        if ($this->helperFactory->getObject('Component\Ebay')->isEnabled()) {
            $this->processOtherListingsMenuItem(Ebay::NICK);
            $this->processWizard($menuModel->get(Ebay::MENU_ROOT_NODE_NICK), Ebay::NICK);
        } else {
            $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        }

        if ($this->helperFactory->getObject('Component\Amazon')->isEnabled()) {
            $this->processOtherListingsMenuItem(Amazon::NICK);
            $this->processWizard($menuModel->get(Amazon::MENU_ROOT_NODE_NICK), Amazon::NICK);
        } else {
            $menuModel->remove(Amazon::MENU_ROOT_NODE_NICK);
        }

        return $menuModel;
    }

    //########################################

    private function processMaintenance(\Magento\Backend\Model\Menu $menuModel)
    {
        $maintenanceMenuItemResource = $menuModel->get(Ebay::MENU_ROOT_NODE_NICK)->isAllowed()
            ? Ebay::MENU_ROOT_NODE_NICK : Amazon::MENU_ROOT_NODE_NICK;

        $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        $menuModel->remove(Amazon::MENU_ROOT_NODE_NICK);

        $maintenanceMenuItem = $this->itemFactory->create([
            'id'       => Maintenance::MENU_ROOT_NODE_NICK,
            'module'   => Module::IDENTIFIER,
            'title'    => 'M2E Pro',
            'resource' => $maintenanceMenuItemResource,
            'action'   => 'm2epro/maintenance',
        ]);

        $menuModel->add($maintenanceMenuItem, null, Maintenance::MENU_POSITION);

        return $menuModel;
    }

    private function processOtherListingsMenuItem($viewNick)
    {
        if (!$this->helperFactory->getObject('View\\'.ucfirst($viewNick))->is3rdPartyShouldBeShown()) {
            $this->pageConfig->addPageAsset('Ess_M2ePro::css/menu/'.$viewNick.'_listing_other.css');
        }
    }

    private function processWizard(\Magento\Backend\Model\Menu\Item $menu, $viewNick)
    {
        $activeBlocker = $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard($viewNick);

        if (!$activeBlocker) {
            return;
        }

        $menu->getChildren()->exchangeArray([]);

        $actionUrl = 'm2epro/wizard_'.$this->helperFactory->getObject('Module\Wizard')->getNick($activeBlocker);

        $globalActiveBlocker = $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard('*');
        if ($globalActiveBlocker && $globalActiveBlocker->getNick() == 'migrationFromMagento1') {
            $actionUrl .= '/index/referrer/'.$viewNick;
        }

        $menu->setAction($actionUrl);
    }

    private function processMenuCacheClearing()
    {
        $previousMenuState = [];
        $currentMenuState = $this->buildMenuStateData();

        /** @var \Ess\M2ePro\Model\Registry $registry */
        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry', self::MENU_STATE_REGISTRY_KEY, 'key', false
        );

        if (!is_null($registry)) {
            $previousMenuState = $registry->getValueFromJson();
        }

        if ($previousMenuState != $currentMenuState) {
            if (is_null($registry)) {
                $registry = $this->activeRecordFactory->getObject('Registry');
                $registry->setKey(self::MENU_STATE_REGISTRY_KEY);
            }

            $registry->setValue(json_encode($currentMenuState))->save();
            
            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }
    }

    private function buildMenuStateData()
    {
        return [
            Ebay::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Ebay')->isEnabled(),
                $this->helperFactory->getObject('View\Ebay')->is3rdPartyShouldBeShown(),
                is_null($this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Ebay::NICK))
            ],
            Amazon::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Amazon')->isEnabled(),
                $this->helperFactory->getObject('View\Amazon')->is3rdPartyShouldBeShown(),
                is_null($this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Amazon::NICK))
            ]
        ];
    }

    //########################################
}