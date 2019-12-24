<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\Task;

use Ess\M2ePro\Model\Listing\Log;

/**
 * Class \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent
 */
abstract class AbstractComponent extends \Ess\M2ePro\Model\Synchronization\AbstractTask
{
    const GENERAL           = 'general';
    const LISTINGS_PRODUCTS = 'listings_products';
    const TEMPLATES         = 'templates';
    const ORDERS            = 'orders';
    const MARKETPLACES      = 'marketplaces';
    const OTHER_LISTINGS    = 'other_listings';
    const POLICIES          = 'policies';

    //########################################

    protected function buildTaskPath($taskPath)
    {
        return ($this->isComponentTask() ? ucfirst($this->getComponent()).'\\' : '').'Synchronization\\'.$taskPath;
    }

    //########################################

    protected function isPossibleToRun()
    {
        if ($this->isComponentLauncherTask() &&
            !$this->getHelper('Component\\'.ucfirst($this->getComponent()))->isEnabled()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    // ---------------------------------------

    protected function beforeStart()
    {
        if (!$this->getParentLockItem()) {
            $this->getLockItem()->create();
            $this->getLockItem()->makeShutdownFunction();
        }

        if (!$this->getParentOperationHistory() || $this->isComponentLauncherTask() || $this->isContainerTask()) {
            $operationHistoryNickSuffix = str_replace('/', '_', trim($this->getFullSettingsPath(), '/'));

            $operationHistoryParentId = $this->getParentOperationHistory() ?
                $this->getParentOperationHistory()->getObject()->getId() : null;

            $this->getOperationHistory()->start(
                'synchronization_'.$operationHistoryNickSuffix,
                $operationHistoryParentId,
                $this->getInitiator()
            );

            $this->getOperationHistory()->makeShutdownFunction();
        }

        $this->configureLogBeforeStart();
        $this->configureProfilerBeforeStart();
        $this->configureLockItemBeforeStart();
    }

    protected function afterEnd()
    {
        $this->configureLockItemAfterEnd();
        $this->configureProfilerAfterEnd();
        $this->configureLogAfterEnd();

        if ($this->intervalIsEnabled()) {
            $this->intervalSetLastTime($this->getHelper('Data')->getCurrentGmtDate(true));
        }

        if (!$this->getParentOperationHistory() || $this->isComponentLauncherTask() || $this->isContainerTask()) {
            $this->getOperationHistory()->stop();
        }

        if (!$this->getParentLockItem()) {
            $this->getLockItem()->remove();
        }
    }

    //########################################

    abstract protected function getComponent();

    // ---------------------------------------

    /**
     * @return bool
     */
    private function isComponentTask()
    {
        return (bool)$this->getComponent();
    }

    /**
     * @return bool
     */
    private function isComponentLauncherTask()
    {
        return $this->isComponentTask() && $this->isLauncherTask();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function isStandardTask()
    {
        return !$this->isComponentLauncherTask() && !$this->isContainerTask();
    }

    //########################################

    /**
     * @return string
     */
    protected function getTitle()
    {
        if ($this->isComponentLauncherTask()) {
            return ucfirst($this->getComponent());
        }

        return parent::getTitle();
    }

    /**
     * @return int
     */
    protected function getLogTask()
    {
        switch ($this->getType()) {
            case self::GENERAL:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_GENERAL;
            case self::LISTINGS_PRODUCTS:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS;
            case self::TEMPLATES:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_TEMPLATES;
            case self::ORDERS:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS;
            case self::MARKETPLACES:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_MARKETPLACES;
            case self::OTHER_LISTINGS:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS;
            case self::POLICIES:
                return \Ess\M2ePro\Model\Synchronization\Log::TASK_POLICIES;
        }

        return parent::getLogTask();
    }

    protected function getActionForLog()
    {
        $action = Log::ACTION_UNKNOWN;

        switch ($this->getNick()) {
            case '/synchronization/list/':
                $action = Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
                break;
            case '/synchronization/relist/':
                $action = Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
                break;
            case '/synchronization/revise/':
                $action = Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
                break;
            case '/synchronization/stop/':
                $action = Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
                break;
        }

        return $action;
    }

    // ---------------------------------------

    /**
     * @return string
     */
    protected function getFullSettingsPath()
    {
        $path = '/'.($this->getComponent() ? strtolower($this->getComponent()).'/' : '');
        $path .= $this->getType() ? strtolower($this->getType()).'/' : '';
        $path .= $this->getNick() ? trim(strtolower($this->getNick()), '/').'/' : '';
        return $path;
    }

    //########################################

    protected function configureLogBeforeStart()
    {
        if ($this->isComponentLauncherTask()) {
            $this->getLog()->setComponentMode($this->getComponent());
        }

        parent::configureLogBeforeStart();
    }

    protected function configureLogAfterEnd()
    {
        if ($this->isComponentLauncherTask()) {
            $this->getLog()->setComponentMode(null);
        }

        parent::configureLogAfterEnd();
    }

    //########################################

    protected function configureLockItemBeforeStart()
    {
        $suffix = $this->getHelper('Module\Translation')->__('Synchronization');

        if ($this->isComponentLauncherTask() || $this->isContainerTask()) {
            $title = $suffix;

            if ($this->isContainerTask()) {
                $title = $this->getTitle().' '.$title;
            }

            if ($this->isComponentTask() && count($this->getHelper('Component')->getEnabledComponents()) > 1) {
                $componentHelper = $this->getHelper('Component\\'.ucfirst($this->getComponent()));

                $this->getActualLockItem()
                    ->setTitle($this->getHelper('Module\Translation')
                        ->__('%component% ' . $title, $componentHelper->getTitle()));
            } else {
                $this->getActualLockItem()->setTitle($this->getHelper('Module\Translation')->__($title));
            }
        }

        $this->getActualLockItem()->setPercents($this->getPercentsStart());

        // M2ePro\TRANSLATIONS
        // Task "%task_title%" is started. Please wait...
        $status = 'Task "%task_title%" is started. Please wait...';
        $title = ($this->isComponentLauncherTask() || $this->isContainerTask()) ?
            $this->getTitle().' '.$suffix : $this->getTitle();

        $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__($status, $title));
    }

    protected function configureLockItemAfterEnd()
    {
        $suffix = $this->getHelper('Module\Translation')->__('Synchronization');

        if ($this->isComponentLauncherTask() || $this->isContainerTask()) {
            $title = $suffix;

            if ($this->isContainerTask()) {
                $title = $this->getTitle().' '.$title;
            }

            if ($this->isComponentTask() && count($this->getHelper('Component')->getEnabledComponents()) > 1) {
                $componentHelper = $this->getHelper('Component\\'.ucfirst($this->getComponent()));

                $this->getActualLockItem()
                    ->setTitle($this->getHelper('Module\Translation')
                        ->__('%component% ' . $title, $componentHelper->getTitle()));
            } else {
                $this->getActualLockItem()->setTitle($this->getHelper('Module\Translation')->__($title));
            }
        }

        $this->getActualLockItem()->setPercents($this->getPercentsEnd());

        // M2ePro\TRANSLATIONS
        // Task "%task_title%" is finished. Please wait...
        $status = 'Task "%task_title%" is finished. Please wait...';
        $title = ($this->isComponentLauncherTask() || $this->isContainerTask()) ?
            $this->getTitle().' '.$suffix : $this->getTitle();

        $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__($status, $title));
    }

    //########################################
}
