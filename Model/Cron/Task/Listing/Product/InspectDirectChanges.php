<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges
 */
class InspectDirectChanges extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'listing/product/inspect_direct_changes';

    const KEY_PREFIX = '/listing/product/inspector';

    const INSTRUCTION_TYPE      = 'inspector_triggered';
    const INSTRUCTION_INITIATOR = 'direct_changes_inspector';
    const INSTRUCTION_PRIORITY  = 10;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->moduleConfiguration = $moduleConfiguration;
    }

    protected function isModeEnabled()
    {
        if (!parent::isModeEnabled()) {
            return false;
        }

        return $this->moduleConfiguration->isEnableListingProductInspectorMode();
    }

    //########################################

    protected function performActions()
    {
        foreach ($this->getHelper('Component')->getEnabledComponents() as $component) {

            $allowedListingsProductsCount = $this->calculateAllowedListingsProductsCount($component);
            if ($allowedListingsProductsCount <= 0) {
                continue;
            }

            $listingsProductsIds = $this->getNextListingsProductsIds($component, $allowedListingsProductsCount);
            if (empty($listingsProductsIds)) {
                $this->setLastListingProductId($component, 0);
                continue;
            }

            $instructionsData = [];

            foreach ($listingsProductsIds as $listingProductId) {
                $instructionsData[] = [
                    'listing_product_id' => $listingProductId,
                    'type'               => self::INSTRUCTION_TYPE,
                    'initiator'          => self::INSTRUCTION_INITIATOR,
                    'priority'           => self::INSTRUCTION_PRIORITY,
                ];
            }

            $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructionsData);

            $this->setLastListingProductId($component, end($listingsProductsIds));
        }
    }

    //########################################

    protected function calculateAllowedListingsProductsCount($component)
    {
        $maxAllowedInstructionsCount = (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::KEY_PREFIX.'/'.$component.'/',
            'max_allowed_instructions_count'
        );

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getCollection();
        $currentInstructionsCount = $collection->applySkipUntilFilter()
            ->addFieldToFilter('component', $component)
            ->addFieldToFilter('initiator', self::INSTRUCTION_INITIATOR)
            ->getSize();

        if ($currentInstructionsCount > $maxAllowedInstructionsCount) {
            return 0;
        }

        return $maxAllowedInstructionsCount - $currentInstructionsCount;
    }

    protected function getNextListingsProductsIds($component, $limit)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('component_mode', $component);
        $collection->addFieldToFilter('id', ['gt' => $this->getLastListingProductId($component)]);
        $collection->getSelect()->order(['id ASC']);
        $collection->getSelect()->limit($limit);

        return $collection->getColumnValues('id');
    }

    //########################################

    protected function getLastListingProductId($component)
    {
        $configValue = $this->getHelper('Module')->getRegistry()->getValue(
            self::KEY_PREFIX.'/'.$component.'/last_listing_product_id/'
        );

        if ($configValue === null) {
            return 0;
        }

        return $configValue;
    }

    protected function setLastListingProductId($component, $listingProductId)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            self::KEY_PREFIX.'/'.$component.'/last_listing_product_id/',
            (int)$listingProductId
        );
    }

    //########################################
}
