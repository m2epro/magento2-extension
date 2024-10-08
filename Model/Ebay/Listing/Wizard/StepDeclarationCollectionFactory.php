<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Ebay\Listing\Wizard;

class StepDeclarationCollectionFactory
{
    public const STEP_SELECT_PRODUCT_SOURCE = 'products-source';
    public const STEP_SELECT_PRODUCTS = 'select-products';
    public const STEP_VALIDATION = 'validation';
    public const STEP_GENERAL_SELECT_CATEGORY_MODE = 'category-mode';
    public const STEP_GENERAL_SELECT_CATEGORY_STEP = 'select-category';
    public const STEP_REVIEW = 'review';

    private static array $steps = [
        Wizard::TYPE_GENERAL => [
            [
                'nick' => self::STEP_SELECT_PRODUCT_SOURCE,
                'route' => '*/ebay_listing_wizard_productSource/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_SELECT_PRODUCTS,
                'route' => '*/ebay_listing_wizard_product/view',
                'back_handler' => \Ess\M2ePro\Model\Ebay\Listing\Wizard\Step\BackHandler\Products::class,
            ],
            [
                'nick' => self::STEP_GENERAL_SELECT_CATEGORY_MODE,
                'route' => '*/ebay_listing_wizard_category/modeView',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_GENERAL_SELECT_CATEGORY_STEP,
                'route' => '*/ebay_listing_wizard_category/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_VALIDATION,
                'route' => '*/ebay_listing_wizard_validation/view',
                'back_handler' => \Ess\M2ePro\Model\Ebay\Listing\Wizard\Step\BackHandler\Validation::class,
            ],
            [
                'nick' => self::STEP_REVIEW,
                'route' => '*/ebay_listing_wizard_review/view',
                'back_handler' => null,
            ],
        ],
        Wizard::TYPE_UNMANAGED => [
            [
                'nick' => self::STEP_GENERAL_SELECT_CATEGORY_STEP,
                'route' => '*/ebay_listing_wizard_category_unmanaged/view',
                'back_handler' => null,
            ],
            [
                'nick' => self::STEP_VALIDATION,
                'route' => '*/ebay_listing_wizard_validation/view',
                'back_handler' => \Ess\M2ePro\Model\Ebay\Listing\Wizard\Step\BackHandler\Validation::class,
            ],
            [
                'nick' => self::STEP_REVIEW,
                'route' => '*/ebay_listing_wizard_review/view',
                'back_handler' => null,
            ],
        ],
    ];

    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $type): StepDeclarationCollection
    {
        $this->validateType($type);

        $steps = [];
        foreach (self::$steps[$type] as $stepData) {
            $steps[] = new StepDeclaration($stepData['nick'], $stepData['route'], $stepData['back_handler']);
        }

        return $this->objectManager->create(StepDeclarationCollection::class, ['steps' => $steps]);
    }

    private function validateType(string $type): void
    {
        if (!in_array($type, [Wizard::TYPE_GENERAL, Wizard::TYPE_UNMANAGED])) {
            throw new \LogicException(sprintf('Listing Wizard type "%s" is not valid.', $type));
        }
    }
}
