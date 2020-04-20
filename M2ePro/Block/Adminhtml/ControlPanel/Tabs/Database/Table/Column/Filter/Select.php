<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\Column\Filter;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\Column\Filter\Select
 */
class Select extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    //########################################

    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    /** @var \Ess\M2ePro\Model\Factory */
    protected $activeRecordFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Block $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    ) {
        $this->helperFactory = $context->getHelperFactory();
        $this->activeRecordFactory  = $context->getActiveRecordFactory();

        parent::__construct($context, $resourceHelper, $data);
    }

    protected function _getOptions()
    {
        $options = [];

        $modelName = $this->getColumn()->getGrid()->getTableModel()->getModelName();
        $htmlName = $this->_getHtmlName();

        $colOptions = $this->activeRecordFactory->getObject($modelName)
            ->getCollection()
            ->getSelect()
            ->group($htmlName)
            ->query();

        if (!empty($colOptions)) {
            $options = [['value' => null, 'label' => '']];
            foreach ($colOptions as $colOption) {
                $options[] = [
                    'value' => $colOption[$htmlName],
                    'label' => $colOption[$htmlName],
                ];
            }
        }

        return $options;
    }

    //########################################
}
