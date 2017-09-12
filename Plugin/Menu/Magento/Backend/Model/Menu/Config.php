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

    public function aroundGetMenu(\Magento\Backend\Model\Menu\Config $interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getMenu', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processGetMenu(\Magento\Backend\Model\Menu\Config $interceptor,
                                      \Closure $callback,
                                      array $arguments)
    {
        /** @var \Magento\Backend\Model\Menu $menuModel */
        $menuModel = $callback(...$arguments);

        if ($this->isProcessed) {
            return $menuModel;
        }

        $this->isProcessed = true;

        // ---------------------------------------

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
            $this->processMaintenance($menuModel);
            return $menuModel;
        } elseif(!is_null($maintenanceMenuState)) {
            $this->helperFactory->getObject('Data\Cache\Permanent')->removeValue(
                self::MAINTENANCE_MENU_STATE_CACHE_KEY
            );
            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }

        // ---------------------------------------

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

            $registry->setValue($this->helperFactory->getObject('Data')->jsonEncode($currentMenuState))->save();

            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }

        // ---------------------------------------

        if ($this->helperFactory->getObject('Module')->isDisabled()) {
            $this->processModuleDisable($menuModel);
            return $menuModel;
        }

        if ($this->helperFactory->getObject('Component\Ebay')->isEnabled()) {
            $this->processWizard($menuModel->get(Ebay::MENU_ROOT_NODE_NICK), Ebay::NICK);
        } else {
            $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        }

        if ($this->helperFactory->getObject('Component\Amazon')->isEnabled()) {
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
    }

    private function processModuleDisable(\Magento\Backend\Model\Menu $menuModel)
    {
        $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        $menuModel->remove(Amazon::MENU_ROOT_NODE_NICK);
    }

    private function processWizard(\Magento\Backend\Model\Menu\Item $menu, $viewNick)
    {
        /** @var \Ess\M2ePro\Model\Wizard $activeBlocker */
        $activeBlocker = $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard($viewNick);

        if (is_null($activeBlocker)) {
            return;
        }

        $menu->getChildren()->exchangeArray([]);

        $actionUrl = 'm2epro/wizard_' . $activeBlocker->getNick();

        if ($activeBlocker instanceof \Ess\M2ePro\Model\Wizard\MigrationFromMagento1) {
            $actionUrl .= '/index/referrer/' . $viewNick;
        }

        $menu->setAction($actionUrl);
    }

    private function buildMenuStateData()
    {
        return [
            Module::IDENTIFIER => [
                $this->helperFactory->getObject('Module')->isDisabled()
            ],
            Ebay::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Ebay')->isEnabled(),
                is_null($this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Ebay::NICK))
            ],
            Amazon::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Amazon')->isEnabled(),
                is_null($this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Amazon::NICK))
            ]
        ];
    }

    //########################################
}