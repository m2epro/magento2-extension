<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\ChangeProcessor;

use Ess\M2ePro\Model\Listing\Product;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'magento_product_change_processor';

    const INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED    = 'magento_product_qty_data_potentially_changed';
    const INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED  = 'magento_product_price_data_potentially_changed';
    const INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED = 'magento_product_status_data_potentially_changed';

    const INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED = 'magmi_plugin_product_changed';

    /** @var Product */
    protected $listingProduct = null;

    /** @var array */
    protected $defaultInstructionTypes = [];

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    public function setListingProduct(Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function setDefaultInstructionTypes(array $instructionTypes)
    {
        $this->defaultInstructionTypes = $instructionTypes;
        return $this;
    }

    //########################################

    abstract public function getTrackingAttributes();

    //########################################

    public function process($changedAttributes = [])
    {
        $listingProductInstructionsData = [];

        foreach ($this->defaultInstructionTypes as $instructionType) {
            $listingProductInstructionsData[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => $instructionType,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => 100,
            ];
        }

        foreach ($this->getInstructionsDataByAttributes($changedAttributes) as $instructionData) {
            $listingProductInstructionsData[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => $instructionData['type'],
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => $instructionData['priority'],
            ];
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()
            ->add($listingProductInstructionsData);
    }

    //########################################

    abstract protected function getInstructionsDataByAttributes(array $attributes);

    //########################################

    protected function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################
}
