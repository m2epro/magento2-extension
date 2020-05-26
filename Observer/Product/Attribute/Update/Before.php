<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\Attribute\Update;

/**
 * Class \Ess\M2ePro\Observer\Product\Attribute\Update\Before
 */
class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $objectManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
        $this->objectManager = $objectManager;
    }

    //########################################

    public function process()
    {
        $changedProductsIds = $this->getEventObserver()->getData('product_ids');
        if (empty($changedProductsIds)) {
            return;
        }

        /** @var \Ess\M2ePro\PublicServices\Product\SqlChange $changesModel */
        $changesModel = $this->objectManager->get('Ess\M2ePro\PublicServices\Product\SqlChange');

        foreach ($changedProductsIds as $productId) {
            $changesModel->markProductChanged($productId);
        }

        $changesModel->applyChanges();
    }

    //########################################
}
