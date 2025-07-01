<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Product\AdvancedFilter;

class Manager
{
    /** @var \Ess\M2ePro\Model\Magento\Product\RuleFactory */
    private $magentoRuleFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory */
    private $ebayRuleFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory */
    private $amazonRuleFactory;
    /** @var \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory */
    private $walmartRuleFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\RuleFactory $magentoRuleFactory,
        \Ess\M2ePro\Model\Ebay\Magento\Product\RuleFactory $ebayRuleFactory,
        \Ess\M2ePro\Model\Amazon\Magento\Product\RuleFactory $amazonRuleFactory,
        \Ess\M2ePro\Model\Walmart\Magento\Product\RuleFactory $walmartRuleFactory,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository
    ) {
        $this->magentoRuleFactory = $magentoRuleFactory;
        $this->ebayRuleFactory = $ebayRuleFactory;
        $this->amazonRuleFactory = $amazonRuleFactory;
        $this->walmartRuleFactory = $walmartRuleFactory;
        $this->repository = $repository;
    }

    public function save(
        \Ess\M2ePro\Model\Magento\Product\Rule $rule,
        string $title,
        array $conditions
    ): \Ess\M2ePro\Model\Listing\Product\AdvancedFilter {
        $conditions = $this->getSerializedConditions($conditions, $rule);

        return $this->repository->save(
            $rule->getNick(),
            $title,
            $conditions,
            \Ess\M2ePro\Helper\Date::createCurrentGmt()
        );
    }

    public function update(
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter $advancedFilter,
        string $title,
        array $conditions
    ): void {
        $rule = $this->getRuleModelByNick($advancedFilter->getModelNick());
        $conditions = $this->getSerializedConditions($conditions, $rule);
        $this->repository->update(
            $advancedFilter,
            $title,
            $conditions,
            \Ess\M2ePro\Helper\Date::createCurrentGmt()
        );
    }

    public function isConditionsValid(array $conditions, \Ess\M2ePro\Model\Magento\Product\Rule $rule): bool
    {
        $conditions = $this->getSerializedConditions($conditions, $rule);
        $rule->loadFromSerialized($conditions);
        if (empty($rule->getConditions()->getConditions())) {
            return false;
        }

        return true;
    }

    private function getSerializedConditions(array $conditions, \Ess\M2ePro\Model\Magento\Product\Rule $rule): string
    {
        $prefix = $rule->getPrefix();
        $conditionsForSerialize = [
            'rule' => [$prefix => $conditions],
        ];

        return $rule->getSerializedFromPost($conditionsForSerialize);
    }

    public function getRuleWithSavedConditions(
        int $entityId,
        string $modelNick,
        ?int $storeId = null
    ): \Ess\M2ePro\Model\Magento\Product\Rule {
        $entity = $this->repository->getAdvancedFilter($entityId);
        if ($entity->getModelNick() !== $modelNick) {
            throw new \LogicException('Model nick don`t match');
        }

        $rule = $this->getRuleModelByNick($modelNick, $storeId);
        $rule->loadFromSerialized($entity->getConditionals());

        return $rule;
    }

    public function getRuleModelByNick(string $nick, ?int $storeId = null): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        if ($nick === \Ess\M2ePro\Model\Magento\Product\Rule::NICK) {
            return $this->magentoRuleFactory->create(\Ess\M2ePro\Model\Magento\Product\Rule::NICK, $storeId);
        }

        if ($nick === \Ess\M2ePro\Model\Ebay\Magento\Product\Rule::NICK) {
            return $this->ebayRuleFactory->create(\Ess\M2ePro\Model\Ebay\Magento\Product\Rule::NICK, $storeId);
        }

        if ($nick === \Ess\M2ePro\Model\Amazon\Magento\Product\Rule::NICK) {
            return $this->amazonRuleFactory->create(\Ess\M2ePro\Model\Amazon\Magento\Product\Rule::NICK, $storeId);
        }

        if ($nick === \Ess\M2ePro\Model\Walmart\Magento\Product\Rule::NICK) {
            return $this->walmartRuleFactory->create(\Ess\M2ePro\Model\Walmart\Magento\Product\Rule::NICK, $storeId);
        }

        throw new \LogicException('Unresolved model nick');
    }
}
