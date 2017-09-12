<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Tax\Rule;

class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    const TAX_CLASS_NAME_PRODUCT   = 'M2E Pro Product Tax Class';
    const TAX_CLASS_NAME_CUSTOMER  = 'M2E Pro Customer Tax Class';
    const TAX_CLASS_NAME_SHIPPING  = 'M2E Pro Shipping Tax Class';

    const TAX_RATE_CODE_PRODUCT    = 'M2E Pro Tax Rate';
    const TAX_RULE_CODE_PRODUCT    = 'M2E Pro Tax Rule';

    const TAX_RATE_CODE_SHIPPING   = 'M2E Pro Shipping Tax Rate';
    const TAX_RULE_CODE_SHIPPING   = 'M2E Pro Shipping Tax Rule';

    protected $classModelFactory;
    protected $rateFactory;
    protected $ruleFactory;
    /** @var $rule \Magento\Tax\Model\Calculation\Rule */
    protected $rule = NULL;

    //########################################

    public function __construct(
        \Magento\Tax\Model\ClassModelFactory $classModelFactory,
        \Magento\Tax\Model\Calculation\RateFactory $rateFactory,
        \Magento\Tax\Model\Calculation\RuleFactory $ruleFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->classModelFactory = $classModelFactory;
        $this->rateFactory = $rateFactory;
        $this->ruleFactory = $ruleFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function getRule()
    {
        return $this->rule;
    }

    //########################################

    public function buildProductTaxRule($rate, $countryId, $customerTaxClassId = NULL)
    {
        $this->buildTaxRule(
            $rate,
            $countryId,
            $customerTaxClassId,
            self::TAX_RATE_CODE_PRODUCT,
            self::TAX_RULE_CODE_PRODUCT,
            self::TAX_CLASS_NAME_PRODUCT
        );
    }

    public function buildShippingTaxRule($rate, $countryId, $customerTaxClassId = NULL)
    {
        $this->buildTaxRule(
            $rate,
            $countryId,
            $customerTaxClassId,
            self::TAX_RATE_CODE_SHIPPING,
            self::TAX_RULE_CODE_SHIPPING,
            self::TAX_CLASS_NAME_SHIPPING
        );
    }

    private function buildTaxRule(
        $rate,
        $countryId,
        $customerTaxClassId = NULL,
        $taxRateCode,
        $taxRuleCode,
        $taxClassName
    )
    {
        // Init product tax class
        // ---------------------------------------
        $productTaxClass = $this->classModelFactory->create()->getCollection()
            ->addFieldToFilter('class_name', $taxClassName)
            ->addFieldToFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT)
            ->getFirstItem();

        if (is_null($productTaxClass->getId())) {
            $productTaxClass->setClassName($taxClassName)
                ->setClassType(\Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_PRODUCT);
            $productTaxClass->save();
        }
        // ---------------------------------------

        // Init customer tax class
        // ---------------------------------------
        if (is_null($customerTaxClassId)) {
            $customerTaxClass = $this->classModelFactory->create()->getCollection()
                ->addFieldToFilter('class_name', self::TAX_CLASS_NAME_CUSTOMER)
                ->addFieldToFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER)
                ->getFirstItem();

            if (is_null($customerTaxClass->getId())) {
                $customerTaxClass->setClassName(self::TAX_CLASS_NAME_CUSTOMER)
                    ->setClassType(\Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER);
                $customerTaxClass->save();
            }

            $customerTaxClassId = $customerTaxClass->getId();
        }
        // ---------------------------------------

        // Init tax rate
        // ---------------------------------------
        $taxCalculationRate = $this->rateFactory->create()->load($taxRateCode, 'code');

        $taxCalculationRate->setCode($taxRateCode)
            ->setRate((float)$rate)
            ->setTaxCountryId((string)$countryId)
            ->setTaxPostcode('*')
            ->setTaxRegionId(0);
        $taxCalculationRate->save();
        // ---------------------------------------

        // Combine tax classes and tax rate in tax rule
        // ---------------------------------------
        $this->rule = $this->ruleFactory->create()->load($taxRuleCode, 'code');

        $this->rule->setCode($taxRuleCode)
            ->setCustomerTaxClassIds([$customerTaxClassId])
            ->setProductTaxClassIds([$productTaxClass->getId()])
            ->setTaxRateIds([$taxCalculationRate->getId()])
            ->setPriority(0);
        $this->rule->save();
        // ---------------------------------------
    }

    //########################################
}