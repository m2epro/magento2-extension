<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;
use Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract;
use \Ess\M2ePro\Model\Amazon\Template\SellingFormat\ChangeProcessor;

class Save extends Settings
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    protected $configuration;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instruction;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Magento\Framework\App\ResourceConnection  $resource,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instruction
    ) {
        parent::__construct($amazonFactory, $context);
        $this->configuration = $configuration;
        $this->dbHelper = $dbHelper;
        $this->instruction = $instruction;
        $this->resource = $resource;
    }

    // ----------------------------------------

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->setJsonContent(['success' => false]);
            return $this->getResult();
        }

        if (
            array_key_exists('business_mode', $post)
            && $this->isChangedBusinessMode((int)$post['business_mode'])
        ) {
            $this->addChangedBusinessModeInstruction();
        }

        $this->configuration->setConfigValues($this->getRequest()->getParams());
        $this->setJsonContent(['success' => true]);
        return $this->getResult();
    }

    // ----------------------------------------

    /**
     * @param int $newBusinessMode
     *
     * @return bool
     */
    private function isChangedBusinessMode(int $newBusinessMode): bool
    {
        return $this->configuration->getBusinessMode() !== $newBusinessMode;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addChangedBusinessModeInstruction(): void
    {
        $select = $this->resource->getConnection()->select();
        $select->from(
            ['alp' => $this->dbHelper->getTableNameWithPrefix('m2epro_amazon_listing_product')],
            null
        );
        $select->joinLeft(
            ['lp' => $this->dbHelper->getTableNameWithPrefix('m2epro_listing_product')],
            'lp.id = alp.listing_product_id',
            null
        );
        $select->joinLeft(
            ['al' => $this->dbHelper->getTableNameWithPrefix('m2epro_amazon_listing')],
            'lp.listing_id = al.listing_id',
            null
        );
        $select->joinLeft(
            ['stf' => $this->dbHelper->getTableNameWithPrefix('m2epro_amazon_template_selling_format')],
            'stf.template_selling_format_id = al.template_selling_format_id',
            null
        );
        $select->joinLeft(
            ['inst' => $this->dbHelper->getTableNameWithPrefix('m2epro_listing_product_instruction')],
            'lp.id = inst.listing_product_id',
            null
        );
        $select->where('stf.business_price_mode = 1');
        $select->where('lp.status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);
        $select->where('inst.id IS NULL');

        $select->reset(\Magento\Framework\DB\Select::COLUMNS);

        $instructionType = ChangeProcessorAbstract::INSTRUCTION_TYPE_PRICE_DATA_CHANGED;
        $instructionInitiator = ChangeProcessor::INSTRUCTION_INITIATOR;

        $select->columns([
            'listing_product_id' => 'lp.id',
            'type' => new \Zend_Db_Expr("'$instructionType'"),
            'initiator' => new \Zend_Db_Expr("'$instructionInitiator'"),
            'priority' => new \Zend_Db_Expr('60'),
        ]);

        $instructionsData = $select->query()->fetchAll();

        $this->instruction->add($instructionsData);
    }
}
