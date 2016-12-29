<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

class Matching extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Order\Matching');
    }

    //########################################

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int)$this->getData('product_id');
    }

    /**
     * @return int
     */
    public function getType()
    {
        return (int)$this->getData('type');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getInputVariationOptions()
    {
        return $this->getSettings('input_variation_options');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOutputVariationOptions()
    {
        return $this->getSettings('output_variation_options');
    }

    public function getComponent()
    {
        return $this->getData('component');
    }

    //########################################

    public function create(
        $productId,
        array $input,
        array $output,
        $component,
        $hash = null
    ) {
        if (is_null($productId) || count($input) == 0 || count($output) == 0) {
            throw new \InvalidArgumentException('Invalid matching data.');
        }

        if (is_null($hash)) {
            $hash = self::generateHash($input);
        }

        $matchingCollection = $this->activeRecordFactory->getObject('Order\Matching')->getCollection();
        $matchingCollection->addFieldToFilter('product_id', (int)$productId);
        $matchingCollection->addFieldToFilter('hash', $hash);

        /** @var \Ess\M2ePro\Model\Order\Matching $matching */
        $matching = $matchingCollection->getFirstItem();

        $matching->addData(array(
            'product_id'               => (int)$productId,
            'input_variation_options'  => $this->getHelper('Data')->jsonEncode($input),
            'output_variation_options' => $this->getHelper('Data')->jsonEncode($output),
            'hash'                     => $hash,
            'component'                => $component,
        ));

        $matching->save();
    }

    public static function generateHash(array $input)
    {
        if (count($input) == 0) {
            return null;
        }

        return sha1(serialize($input));
    }

    //########################################
}