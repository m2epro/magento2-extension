<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Wizard;

use Ess\M2ePro\Model\Wizard;

class VersionDowngrade extends Wizard
{
    public const NICK = 'versionDowngrade';

    /** @var string[] */
    protected $steps = [
        'readNotification',
    ];

    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;
    /** @var \Ess\M2ePro\Helper\Module\Maintenance */
    private $maintenanceHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Setup */
    private $setupResource;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    public function __construct(
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Helper\Module\Maintenance $maintenanceHelper,
        \Ess\M2ePro\Model\ResourceModel\Setup $setupResource,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->moduleHelper = $moduleHelper;
        $this->wizardHelper = $wizardHelper;
        $this->maintenanceHelper = $maintenanceHelper;
        $this->setupResource = $setupResource;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param mixed $view
     *
     * @return bool
     */
    public function isActive($view): bool
    {
        return true;
    }

    public function getNick(): string
    {
        return self::NICK;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function isVersionDowngrade(): bool
    {
        $installedVersion = $this->moduleHelper->getDataVersion();

        $setupTable = $this->setupResource->getMainTable();
        if (!$this->resourceConnection->getConnection()->isTableExists($setupTable)) {
            return false;
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($setupTable, 'version_to')
            ->order('id DESC')
            ->limit(1);

        $lastUpgradeVersion = $this->resourceConnection->getConnection()->fetchOne($select);

        return version_compare($lastUpgradeVersion, $installedVersion, '>')
            && version_compare($lastUpgradeVersion, '6.0.0', '<');
    }

    public function startRepairProcess(): void
    {
        $this->wizardHelper->setStatus(
            \Ess\M2ePro\Model\Wizard\VersionDowngrade::NICK,
            \Ess\M2ePro\Helper\Module\Wizard::STATUS_ACTIVE
        );

        $this->maintenanceHelper->enable();
    }

    public function finishRepairProcess(): void
    {
        $this->wizardHelper->setStatus(
            \Ess\M2ePro\Model\Wizard\VersionDowngrade::NICK,
            \Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED
        );

        $this->maintenanceHelper->disable();
    }
}
