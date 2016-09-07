<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Indexes;

class Enable extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $productIndex;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\Index $productIndex,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->productIndex = $productIndex;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if (!$this->productIndex->isIndexManagementEnabled()) {
            return;
        }

        $enabledIndexes = array();

        foreach ($this->productIndex->getIndexes() as $code) {
            if ($this->productIndex->isDisabledIndex($code) && $this->productIndex->enableReindex($code)) {
                $this->productIndex->forgetDisabledIndex($code);
                $enabledIndexes[] = $code;
            }
        }

        $executedIndexes = array();

        foreach ($enabledIndexes as $code) {
            if ($this->productIndex->requireReindex($code) && $this->productIndex->executeReindex($code)) {
                $executedIndexes[] = $code;
            }
        }

        if (count($executedIndexes) <= 0) {
            return;
        }

        // M2ePro\TRANSLATIONS
        // Product reindex was executed.
        $this->activeRecordFactory->getObject('Synchronization\Log')->addMessage(
            $this->getHelper('Module\Translation')->__('Product reindex was executed.'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
        );
    }

    //########################################
}