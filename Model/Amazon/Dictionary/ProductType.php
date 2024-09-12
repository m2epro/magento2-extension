<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

use Ess\M2ePro\Model\Amazon\ProductType\Validator\ValidatorBuilder;
use Ess\M2ePro\Model\Amazon\ProductType\Validator\ValidatorInterface;
use Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType as ProductTypeResource;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    private array $flatScheme;

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(ProductTypeResource::class);
    }

    public function create(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        string $nick,
        string $title,
        array $schema,
        array $variationThemes,
        array $attributesGroups,
        \DateTime $serverUpdateDate,
        \DateTime $clientUpdateDate
    ): self {
        $this->setData(ProductTypeResource::COLUMN_MARKETPLACE_ID, (int)$marketplace->getId())
             ->setData(ProductTypeResource::COLUMN_NICK, $nick)
             ->setData(ProductTypeResource::COLUMN_TITLE, $title)
             ->setScheme($schema)
             ->setVariationThemes($variationThemes)
             ->setAttributesGroups($attributesGroups)
             ->setClientDetailsLastUpdateDate($clientUpdateDate)
             ->setServerDetailsLastUpdateDate($serverUpdateDate);

        return $this;
    }

    // ----------------------------------------

    public function getMarketplaceId(): int
    {
        return (int)$this->getData(
            ProductTypeResource::COLUMN_MARKETPLACE_ID
        );
    }

    public function getNick(): string
    {
        return (string)$this->getData(ProductTypeResource::COLUMN_NICK);
    }

    public function getTitle(): string
    {
        return (string)$this->getData(ProductTypeResource::COLUMN_TITLE);
    }

    public function setScheme(array $schema): self
    {
        $this->setData(ProductTypeResource::COLUMN_SCHEMA, json_encode($schema));

        return $this;
    }

    public function getScheme(): array
    {
        $value = $this->getData(ProductTypeResource::COLUMN_SCHEMA);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }

    public function setVariationThemes(array $variationThemes): self
    {
        $this->setData(ProductTypeResource::COLUMN_VARIATION_THEMES, json_encode($variationThemes));

        return $this;
    }

    public function hasVariationThemes(): bool
    {
        return !empty($this->getVariationThemes());
    }

    public function getVariationThemes(): array
    {
        $value = $this->getData(ProductTypeResource::COLUMN_VARIATION_THEMES);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }

    public function hasVariationTheme(string $variationTheme): bool
    {
        return isset($this->getVariationThemes()[$variationTheme]);
    }

    public function getVariationThemesAttributes(string $variationTheme): array
    {
        return $this->getVariationThemes()[$variationTheme]['attributes'] ?? [];
    }

    public function setAttributesGroups(array $attributesGroups): self
    {
        $this->setData(ProductTypeResource::COLUMN_ATTRIBUTES_GROUPS, json_encode($attributesGroups));

        return $this;
    }

    public function getAttributesGroups(): array
    {
        $value = $this->getData(ProductTypeResource::COLUMN_ATTRIBUTES_GROUPS);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }

    public function getClientDetailsLastUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getData(ProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE)
        );
    }

    public function setClientDetailsLastUpdateDate(\DateTime $value): self
    {
        $this->setData(ProductTypeResource::COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE, $value->format('Y-m-d H:i:s'));

        return $this;
    }

    public function getServerDetailsLastUpdateDate(): \DateTime
    {
        return \Ess\M2ePro\Helper\Date::createDateGmt(
            $this->getData(ProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE)
        );
    }

    public function setServerDetailsLastUpdateDate(\DateTime $value): self
    {
        $this->setData(ProductTypeResource::COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE, $value->format('Y-m-d H:i:s'));

        return $this;
    }

    public function isInvalid(): bool
    {
        return (bool)$this->getData(ProductTypeResource::COLUMN_INVALID);
    }

    public function markAsInvalid(): self
    {
        $this->setData(ProductTypeResource::COLUMN_INVALID, (int)true);

        return $this;
    }

    public function markAsValid(): self
    {
        $this->setData(ProductTypeResource::COLUMN_INVALID, (int)false);

        return $this;
    }

    // ----------------------------------------

    public function getValidatorByPath(string $path): ValidatorInterface
    {
        $flatScheme = $this->getFlatScheme();
        if (!array_key_exists($path, $flatScheme)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not found specific path');
        }

        $validatorBuilderData = $flatScheme[$path];
        $validatorBuilderData['group_title'] = $this->getGroupTitleByNick($validatorBuilderData['group_nick']);

        return (new ValidatorBuilder($validatorBuilderData))->build();
    }

    public function findNameByProductTypeCode(string $code): string
    {
        $flatScheme = $this->getFlatScheme();

        if (!array_key_exists($code, $flatScheme)) {
            return '';
        }

        return $flatScheme[$code]['title'];
    }

    private function getFlatScheme(): array
    {
        if (!isset($this->flatScheme)) {
            $this->flatScheme = $this->convertSchemeToFlat($this->getScheme());
        }

        return $this->flatScheme;
    }

    private function convertSchemeToFlat(array $array, array $parentAttributes = []): array
    {
        $result = [];
        foreach ($array as $item) {
            if ($parentAttributes !== []) {
                if ($parentAttributes['title'] !== $item['title']) {
                    $item['title'] = $parentAttributes['title'] . ' >> ' . $item['title'];
                }
                $item['name'] = $parentAttributes['name'] . '/' . $item['name'];
            }

            if (array_key_exists('children', $item) && $item['children'] && $item['type'] !== null) {
                $result += $this->convertSchemeToFlat($item['children'], [
                    'name' => $item['name'],
                    'title' => $item['title'],
                ]);
                continue;
            }

            $result[$item['name']] = $item;
        }

        return $result;
    }

    private function getGroupTitleByNick(string $groupNick): string
    {
        return $this->getAttributesGroups()[$groupNick] ?? '';
    }
}
