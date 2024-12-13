<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater;

use Ess\M2ePro\Model\Magento\Product\Variation;

class MagentoVariations
{
    private array $magentoVariations;
    /** @var list<int, array{product_id: int, replaced_title: string, new_title: string}>*/
    private array $options = [];
    private ?string $variationAttribute = null;

    /**
     * @param array{
     *      set: list<string, string[]>,
     *      variations: list<list<array{
     *          product_id: int,
     *          product_type: string,
     *          attribute: string,
     *          option: string
     *      }>>,
     *      additional: array
     *  } $magentoVariations
     *
     * @see \Ess\M2ePro\Model\Magento\Product\Variation::getVariationsTypeStandard()
     */
    public function __construct(array $magentoVariations)
    {
        if (!self::canBeCreated($magentoVariations)) {
            throw new \RuntimeException('Cannot be created due to array structure');
        }

        $this->magentoVariations = $magentoVariations;
    }

    public static function canBeCreated(array $magentoVariations): bool
    {
        return !empty($magentoVariations['set'][Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL])
            && !empty($magentoVariations['variations']);
    }

    public function getChangedMagentoVariations(): array
    {
        return $this->change($this->magentoVariations);
    }

    private function change(array $variations): array
    {
        $variationAttribute = Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL;

        // Replace Variation Attribute
        // ---------------------------------------
        if ($this->variationAttribute !== null) {
            $variationAttribute = $this->variationAttribute;

            // In set
            $variations['set'][$variationAttribute] = $this->magentoVariations['set'][
                Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL
            ];
            unset($variations['set'][Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL]);

            // In variations
            foreach ($variations['variations'] as &$list) {
                foreach ($list as &$item) {
                    $item['attribute'] = $variationAttribute;
                }
            }
        }

        // Replace Variation Options
        // ---------------------------------------
        foreach ($this->options as $option) {
            // In set
            foreach ($variations['set'][$variationAttribute] as $key => $currentTitle) {
                if ($currentTitle === $option['replaced_title']) {
                    $variations['set'][$variationAttribute][$key] = $option['new_title'];
                }
            };

            // In variations
            foreach ($variations['variations'] as &$list) {
                foreach ($list as &$item) {
                    if ((int)$item['product_id'] === $option['product_id']) {
                        $item['option'] = $option['new_title'];
                    }
                }
            }
        }

        return $variations;
    }

    public function setVariationAttribute(string $variationAttribute): void
    {
        $this->variationAttribute = $variationAttribute;
    }

    public function addOption(
        int $productId,
        string $replacedOptionTitle,
        string $newOptionTitle
    ): void {
        $this->options[$productId] = [
            'product_id' => $productId,
            'replaced_title' => $replacedOptionTitle,
            'new_title' => $newOptionTitle,
        ];
    }
}
