<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Ebay;

class BestOfferTracker extends PriceTracker
{
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory;
    private \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\PriceConditionFactory $priceConditionFactory;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes;
    private \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration $configuration,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\PriceConditionFactory $priceConditionFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        parent::__construct(
            $configuration,
            $queryBuilderFactory,
            $attributesQueryBuilder,
            $priceConditionFactory,
            $logger
        );
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->priceConditionFactory = $priceConditionFactory;
        $this->magentoAttributes = $magentoAttributes;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
        $this->dataHelper = $dataHelper;
    }

    public function getType(): string
    {
        return \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::TYPE_BEST_OFFER;
    }

    public function getDataQuery(): \Magento\Framework\DB\Select
    {
        $productQuery = $this->productSubQuery();

        $select = $this->queryBuilderFactory->createSelect();

        $select->addSelect('listing_product_id', 'product.listing_product_id');
        $select->addSelect('online_best_offer', 'product.online_best_offer');

        $select->from('product', $productQuery);

        $select->addSelect(
            'calculated_price',
            (string)new \Zend_Db_Expr(
                sprintf(
                    '@productCalculatedPrice := MIN( %s )',
                    $this->priceConditionFactory->create($this->getChannel())->getCondition()
                )
            )
        );

        $bestOfferPolicies = $this->loadBestOfferData();
        $modeConditions = [];
        $acceptPriceConditions = [];
        $rejectPriceConditions = [];
        foreach ($bestOfferPolicies as $policyData) {
            $modeConditions[] = sprintf(
                'WHEN product.selling_template_id = %s THEN %s',
                $policyData['id'],
                $policyData['mode']
            );
            $acceptPriceConditions[] = $this->calculateAccept(
                (int)$policyData['id'],
                (int)$policyData['accept_mode'],
                (string)$policyData['accept_value'],
                (string)$policyData['accept_attribute']
            );
            $rejectPriceConditions[] = $this->calculateReject(
                (int)$policyData['id'],
                (int)$policyData['reject_mode'],
                (string)$policyData['reject_value'],
                (string)$policyData['reject_attribute']
            );
        }

        $select->addSelect('best_offer_mode', sprintf('(CASE %s END)', implode(' ', $modeConditions)));
        $select->addSelect('best_offer_accept_price', sprintf('(CASE %s END)', implode(' ', $acceptPriceConditions)));
        $select->addSelect('best_offer_reject_price', sprintf('(CASE %s END)', implode(' ', $rejectPriceConditions)));

        $select->andWhere('product.status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED);

        $select->leftJoin(
            'selling_policy',
            'm2epro_ebay_template_selling_format',
            'product.selling_template_id = selling_policy.template_selling_format_id'
        );
        $select->andWhere('selling_policy.best_offer_mode = 1');

        $select->addGroup('product.listing_product_id');
        $select->addGroup('product.online_best_offer');

        return $select->getQuery();
    }

    public function processQueryRow(array $row): ?array
    {
        $isChangedBestOffer = $this->isChangedBestOffer(
            (string)$row['online_best_offer'],
            (int)$row['best_offer_mode'],
            (float)$row['best_offer_accept_price'],
            (float)$row['best_offer_reject_price']
        );

        if (!$isChangedBestOffer) {
            return null;
        }

        return parent::processQueryRow($row);
    }

    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $parent = parent::productSubQuery();
        $parent->addSelect('online_best_offer', 'c_lp.online_best_offer');
        $parent->addSelect('parent_product_id', 'lp.product_id');

        return $parent;
    }

    private function loadBestOfferData(): array
    {
        $select = $this->queryBuilderFactory->createSelect();
        $select->addSelect('id', 'sf.template_selling_format_id');
        $select->addSelect('mode', 'sf.best_offer_mode');
        $select->addSelect('accept_mode', 'sf.best_offer_accept_mode');
        $select->addSelect('accept_value', 'sf.best_offer_accept_value');
        $select->addSelect('accept_attribute', 'sf.best_offer_accept_attribute');
        $select->addSelect('reject_mode', 'sf.best_offer_reject_mode');
        $select->addSelect('reject_value', 'sf.best_offer_reject_value');
        $select->addSelect('reject_attribute', 'sf.best_offer_reject_attribute');

        $select->from('sf', 'm2epro_ebay_template_selling_format');

        $result = $select->fetchAll();

        $this->logger->debug('Loaded synchronise policy data for Best Offer Condition', [
            'tracker' => $this,
            'query' => (string)$select,
            'result' => $result,
        ]);

        return $result;
    }

    private function calculateAccept(
        int $sellingPolicyId,
        int $acceptMode,
        string $acceptValue,
        string $acceptAttributeCode
    ): string {
        $then = '0';

        if ($acceptMode === \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_PERCENTAGE) {
            $then = sprintf('@productCalculatedPrice * %s / 100', (float)$acceptValue);
        }

        if ($acceptMode === \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE) {
            try {
                $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute(
                    $acceptAttributeCode,
                    'product.store_id',
                    'product.parent_product_id'
                );
                if (
                    $this->magentoAttributes->isFrontendInputPrice($acceptAttributeCode)
                    && $this->moduleConfiguration->isEnableMagentoAttributePriceTypeConvertingMode()
                ) {
                    $attributeQuery = sprintf('product.currency_rate * (%s)', $attributeQuery);
                }

                $then = $attributeQuery;
            } catch (\Throwable $throwable) {
                $message = 'Error when trying create accept attribute query. Message: ' . $throwable->getMessage();
                $this->logger->error($message, [
                    'tracker' => $this,
                ]);
            }
        }

        return sprintf(
            'WHEN product.selling_template_id = %s THEN ROUND( (%s), 2 )',
            $sellingPolicyId,
            $then
        );
    }

    private function calculateReject(
        int $sellingPolicyId,
        int $rejectMode,
        string $rejectValue,
        string $rejectAttributeCode
    ): string {
        $then = '0';

        if ($rejectMode === \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_PERCENTAGE) {
            $then = sprintf('@productCalculatedPrice * %s / 100', $rejectValue);
        }

        if ($rejectMode === \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE) {
            try {
                $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute(
                    $rejectAttributeCode,
                    'product.store_id',
                    'product.parent_product_id'
                );

                if (
                    $this->magentoAttributes->isFrontendInputPrice($rejectAttributeCode)
                    && $this->moduleConfiguration->isEnableMagentoAttributePriceTypeConvertingMode()
                ) {
                    $attributeQuery = sprintf('product.currency_rate * (%s)', $attributeQuery);
                }

                $then = (string)$attributeQuery;
            } catch (\Throwable $throwable) {
                $message = 'Error when trying create reject attribute query. Message: ' . $throwable->getMessage();
                $this->logger->error($message, [
                    'tracker' => $this,
                ]);
            }
        }

        return sprintf(
            'WHEN product.selling_template_id = %s THEN ROUND( (%s), 2 )',
            $sellingPolicyId,
            $then
        );
    }

    private function isChangedBestOffer(
        string $onlineBestOfferHash,
        int $bestOfferMode,
        float $acceptPrice,
        float $rejectPrice
    ): bool {
        $currentBestOffer = $this->getCurrentBestOffer($bestOfferMode, $acceptPrice, $rejectPrice);
        $currentBestOfferHash = $this->dataHelper->hashString(
            \Ess\M2ePro\Helper\Json::encode($currentBestOffer),
            'md5'
        );

        return $onlineBestOfferHash !== $currentBestOfferHash;
    }

    private function getCurrentBestOffer(
        int $bestOfferMode,
        float $acceptPrice,
        float $rejectPrice
    ): array {
        if ($bestOfferMode === \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_MODE_NO) {
            return ['bestoffer_mode' => false];
        }

        $acceptPrice = ($acceptPrice > 0)
            ? round($acceptPrice, 2)
            : 0;

        $rejectPrice = ($rejectPrice > 0)
            ? round($rejectPrice, 2)
            : 0;

        return [
            'bestoffer_mode' => true,
            'bestoffer_accept_price' => $acceptPrice,
            'bestoffer_reject_price' => $rejectPrice,
        ];
    }
}
