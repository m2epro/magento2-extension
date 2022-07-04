<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Wizard
{
    public const STATUS_NOT_STARTED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_SKIPPED = 3;

    private const KEY_VIEW = 'view';
    private const KEY_STATUS = 'status';
    private const KEY_STEP = 'step';
    private const KEY_PRIORITY = 'priority';
    private const KEY_TYPE = 'type';

    private const TYPE_SIMPLE = 0;
    private const TYPE_BLOCKER = 1;

    /** @var null */
    private $cache = null;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Magento\Framework\Code\NameBuilder */
    private $nameBuilder;
    /** @var \Magento\Framework\View\LayoutInterface */
    private $layout;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructureHelper;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCache;

    /**
     * @param \Magento\Framework\Code\NameBuilder $nameBuilder
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructureHelper
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
     */
    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Magento\Framework\View\LayoutInterface $layout,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructureHelper,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCache
    ) {
        $this->nameBuilder = $nameBuilder;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->resourceConnection = $resourceConnection;
        $this->layout = $layout;
        $this->moduleDatabaseStructureHelper = $moduleDatabaseStructureHelper;
        $this->permanentCache = $permanentCache;
    }

    /**
     * @param \Ess\M2ePro\Model\Wizard $wizard
     *
     * @return string|null
     */
    public function getNick(\Ess\M2ePro\Model\Wizard $wizard): ?string
    {
        return $wizard->getNick();
    }

    /**
     * Wizards Factory
     *
     * @param string $nick
     *
     * @return \Ess\M2ePro\Model\Wizard
     */
    public function getWizard($nick)
    {
        return $this->activeRecordFactory->getObject('Wizard\\' . ucfirst($nick));
    }

    /**
     * @param $nick
     * @param $view
     *
     * @return bool
     */
    public function isNotStarted($nick, $view = null)
    {
        return $this->getStatus($nick) == self::STATUS_NOT_STARTED &&
            $this->getWizard($nick)->isActive($view);
    }

    /**
     * @param $nick
     * @param $view
     *
     * @return bool
     */
    public function isActive($nick, $view = null)
    {
        return $this->getStatus($nick) == self::STATUS_ACTIVE &&
            $this->getWizard($nick)->isActive($view);
    }

    /**
     * @param $nick
     *
     * @return bool
     */
    public function isCompleted($nick)
    {
        return $this->getStatus($nick) == self::STATUS_COMPLETED;
    }

    /**
     * @param $nick
     *
     * @return bool
     */
    public function isSkipped($nick)
    {
        return $this->getStatus($nick) == self::STATUS_SKIPPED;
    }

    /**
     * @param $nick
     *
     * @return bool
     */
    public function isFinished($nick)
    {
        return $this->isCompleted($nick) || $this->isSkipped($nick);
    }

    /**
     * @param $nick
     *
     * @return mixed
     */
    public function getView($nick)
    {
        return $this->getConfigValue($nick, self::KEY_VIEW);
    }

    /**
     * @param $nick
     *
     * @return mixed
     */
    public function getStatus($nick)
    {
        return $this->getConfigValue($nick, self::KEY_STATUS);
    }

    /**
     * @param $nick
     * @param $status
     *
     * @return void
     */
    public function setStatus($nick, $status = self::STATUS_NOT_STARTED)
    {
        $this->setConfigValue($nick, self::KEY_STATUS, $status);
    }

    /**
     * @param $nick
     *
     * @return mixed
     */
    public function getStep($nick)
    {
        return $this->getConfigValue($nick, self::KEY_STEP);
    }

    /**
     * @param $nick
     * @param $step
     *
     * @return void
     */
    public function setStep($nick, $step = null)
    {
        $this->setConfigValue($nick, self::KEY_STEP, $step);
    }

    /**
     * @param $nick
     *
     * @return mixed
     */
    public function getPriority($nick)
    {
        return $this->getConfigValue($nick, self::KEY_PRIORITY);
    }

    /**
     * @param $nick
     *
     * @return mixed
     */
    public function getType($nick)
    {
        return $this->getConfigValue($nick, self::KEY_TYPE);
    }

    /**
     * @param string $view
     *
     * @return null|\Ess\M2ePro\Model\Wizard
     */
    public function getActiveWizard($view)
    {
        $wizards = $this->getAllWizards($view);

        /** @var \Ess\M2ePro\Model\Wizard $wizard */
        foreach ($wizards as $wizard) {
            if (
                $this->isNotStarted($this->getNick($wizard), $view) ||
                $this->isActive($this->getNick($wizard), $view)
            ) {
                return $wizard;
            }
        }

        return null;
    }

    /**
     * @param $view
     *
     * @return \Ess\M2ePro\Model\Wizard|null
     */
    public function getActiveBlockerWizard($view)
    {
        $wizards = $this->getAllWizards($view);

        /** @var \Ess\M2ePro\Model\Wizard $wizard */
        foreach ($wizards as $wizard) {
            if ($this->getType($this->getNick($wizard)) != self::TYPE_BLOCKER) {
                continue;
            }

            if (
                $this->isNotStarted($this->getNick($wizard), $view) ||
                $this->isActive($this->getNick($wizard), $view)
            ) {
                return $wizard;
            }
        }

        return null;
    }

    /**
     * @param $view
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getAllWizards($view): array
    {
        if ($this->cache === null) {
            $this->loadCache();
        }

        $wizards = [];
        foreach ($this->cache as $nick => $wizard) {
            if ($wizard['view'] !== '*' && $wizard['view'] != $view) {
                continue;
            }

            try {
                $wizards[] = $this->getWizard($nick);
                // @codingStandardsIgnoreLine
            } catch (\ReflectionException $e) {
                //wizards after migration from m1
            }
        }

        return $wizards;
    }

    /**
     * @param $block
     * @param $nick
     */
    public function createBlock($block, $nick = '')
    {
        return $this->layout->createBlock(
            $this->nameBuilder->buildClassName([
                'Ess',
                'M2ePro',
                'Block',
                'Adminhtml',
                'Wizard',
                $nick,
                $block,
            ]),
            '',
            ['data' => ['nick' => $nick]]
        );
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    private function loadCache(): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->moduleDatabaseStructureHelper
            ->getTableNameWithPrefix('m2epro_wizard');

        $this->cache = $connection->fetchAll(
            $connection->select()->from($tableName, '*')
        );

        usort($this->cache, function ($a, $b) {
            if ($a['type'] != $b['type']) {
                return $a['type'] == \Ess\M2ePro\Helper\Module\Wizard::TYPE_BLOCKER ? -1 : 1;
            }

            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return $a['priority'] > $b['priority'] ? 1 : -1;
        });

        foreach ($this->cache as $id => $wizard) {
            $this->cache[$wizard['nick']] = $wizard;
            unset($this->cache[$id]);
        }

        $this->permanentCache->setValue(
            'wizard',
            json_encode($this->cache),
            ['wizard'],
            60 * 60
        );
    }

    /**
     * @param $nick
     * @param $key
     *
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getConfigValue($nick, $key)
    {
        if ($this->cache !== null) {
            return $this->cache[$nick][$key];
        }

        if (($cache = $this->permanentCache->getValue('wizard')) !== null) {
            $this->cache = json_decode($cache, true);

            return $this->cache[$nick][$key];
        }

        $this->loadCache();

        return $this->cache[$nick][$key];
    }

    /**
     * @param $nick
     * @param $key
     * @param $value
     *
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function setConfigValue($nick, $key, $value)
    {
        if ($this->cache === null) {
            $this->loadCache();
        }

        $this->cache[$nick][$key] = $value;

        $this->permanentCache->setValue(
            'wizard',
            json_encode($this->cache),
            ['wizard'],
            60 * 60
        );

        $connWrite = $this->resourceConnection->getConnection();
        $tableName = $this->moduleDatabaseStructureHelper->getTableNameWithPrefix('m2epro_wizard');

        $connWrite->update(
            $tableName,
            [$key => $value],
            ['nick = ?' => $nick]
        );
    }
}
