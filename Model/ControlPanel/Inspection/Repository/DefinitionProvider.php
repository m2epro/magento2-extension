<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Repository;

class DefinitionProvider
{
    const GROUP_ORDERS    = 'orders';
    const GROUP_PRODUCTS  = 'products';
    const GROUP_STRUCTURE = 'structure';
    const GROUP_GENERAL   = 'general';

    const EXECUTION_SPEED_SLOW = 'slow';
    const EXECUTION_SPEED_FAST = 'fast';

    private $inspectionsData = [
        [
            'nick' => 'AmazonProductWithoutVariations',
            'title' => 'Amazon products without variations',
            'description' => '',
            'group' => self::GROUP_PRODUCTS,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\AmazonProductWithoutVariations::class
        ],
        [
            'nick' => 'BrokenTables',
            'title' => 'Broken tables',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\BrokenTables::class
        ],
        [
            'nick' => 'ConfigsValidity',
            'title' => 'Configs validity',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ConfigsValidity::class
        ],
        [
            'nick' => 'EbayItemIdStructure',
            'title' => 'Ebay item id N\A',
            'description' => '',
            'group' => self::GROUP_PRODUCTS,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\EbayItemIdStructure::class
        ],
        [
            'nick' => 'ExtensionCron',
            'title' => 'Extension Cron',
            'description' => '
            - Cron [runner] does not work<br>
            - Cron [runner] is not working more than 30 min<br>
            - Cron [runner] is disabled by developer
            ',
            'group' => self::GROUP_GENERAL,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ExtensionCron::class
        ],
        [
            'nick' => 'FilesPermissions',
            'title' => 'Files and Folders permissions',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_SLOW,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\FilesPermissions::class
        ],
        [
            'nick' => 'FilesValidity',
            'title' => 'Files validity',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\FilesValidity::class,
        ],
        [
            'nick' => 'ListingProductStructure',
            'title' => 'Listing product structure',
            'description' => '',
            'group' => self::GROUP_PRODUCTS,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ListingProductStructure::class,
        ],
        [
            'nick' => 'MagentoSettings',
            'title' => 'Magento settings',
            'description' => '
            - Non-default Magento timezone set<br>
            - GD library is installed<br>
            - [APC|Memchached|Redis] Cache is enabled<br>
            ',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\MagentoSettings::class,
        ],
        [
            'nick' => 'NonexistentTemplates',
            'title' => 'Nonexistent template',
            'description' => '',
            'group' => self::GROUP_PRODUCTS,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\NonexistentTemplates::class,
        ],
        [
            'nick' => 'OrderItemStructure',
            'title' => 'Order item structure',
            'description' => '',
            'group' => self::GROUP_ORDERS,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\OrderItemStructure::class,
        ],
        [
            'nick' => 'RemovedStores',
            'title' => 'Removed stores',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\RemovedStores::class,
        ],
        [
            'nick' => 'ServerConnection',
            'title' => 'Connection with server',
            'description' => '',
            'group' => self::GROUP_GENERAL,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ServerConnection::class,
        ],
        [
            'nick' => 'ShowM2eProLoggers',
            'title' => 'Show M2ePro loggers',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_SLOW,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\ShowM2eProLoggers::class,
        ],
        [
            'nick' => 'SystemRequirements',
            'title' => 'System Requirements',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\SystemRequirements::class,
        ],
        [
            'nick' => 'TablesStructureValidity',
            'title' => 'Tables structure validity',
            'description' => '',
            'group' => self::GROUP_STRUCTURE,
            'execution_speed_group' => self::EXECUTION_SPEED_FAST,
            'handler' => \Ess\M2ePro\Model\ControlPanel\Inspection\Inspector\TablesStructureValidity::class,
        ],
    ];

    /** @var \Ess\M2ePro\Model\ControlPanel\Inspection\DefinitionFactory */
    private $definitionFactory;

    /** @var \Ess\M2ePro\Model\Requirements\Manager */
    private $requirementsManager;

    public function __construct(
        \Ess\M2ePro\Model\ControlPanel\Inspection\DefinitionFactory $definitionFactory,
        \Ess\M2ePro\Model\Requirements\Manager $requirementsManager
    ) {
        $this->definitionFactory = $definitionFactory;
        $this->requirementsManager = $requirementsManager;
    }

    /**
     * @return \Ess\M2ePro\Model\ControlPanel\Inspection\Definition[]
     */
    public function getDefinitions()
    {
        $definitions = [];

        foreach ($this->inspectionsData as $inspectionData) {
            if ($inspectionData['nick'] == 'SystemRequirements') {
                foreach ($this->requirementsManager->getChecks() as $check) {
                    $inspectionData['description'] .= "- {$check->getRenderer()->getTitle()}:
                    {$check->getRenderer()->getMin()}<br>";
                }
            }

            $definitions[] = $this->definitionFactory->create(
                [
                    'nick'                => $inspectionData['nick'],
                    'title'               => $inspectionData['title'],
                    'description'         => $inspectionData['description'],
                    'group'               => $inspectionData['group'],
                    'executionSpeedGroup' => $inspectionData['execution_speed_group'],
                    'handler'             => $inspectionData['handler'],
                ]
            );
        }

        return $definitions;
    }
}
