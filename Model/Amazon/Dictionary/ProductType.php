<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Dictionary;

use Ess\M2ePro\Model\Amazon\ProductType\Validator\ValidatorBuilder;
use Ess\M2ePro\Model\Amazon\ProductType\Validator\ValidatorInterface;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;
    /** @var ?array */
    private $flatScheme;
    /** @var ?array */
    private $groups;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->productTypeHelper = $productTypeHelper;
    }

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::class);
    }

    /**
     * @return int
     */
    public function getMarketplaceId(): int
    {
        return (int)$this->getData('marketplace_id');
    }

    /**
     * @param int $marketplaceId
     *
     * @return $this
     */
    public function setMarketplaceId(int $marketplaceId): self
    {
        $this->setData('marketplace_id', $marketplaceId);

        return $this;
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return (string)$this->getData('nick');
    }

    /**
     * @param string $nick
     *
     * @return $this
     */
    public function setNick(string $nick): self
    {
        $this->setData('nick', $nick);

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string)$this->getData('title');
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->setData('title', $title);

        return $this;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getScheme(): array
    {
        $scheme = \Ess\M2ePro\Helper\Json::decode((string)$this->getData('scheme'));

        return is_array($scheme) ? $scheme : [];
    }

    /**
     * @param array $scheme
     *
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setScheme(array $scheme): self
    {
        $this->setData('scheme', \Ess\M2ePro\Helper\Json::encode($scheme));

        return $this;
    }

    /**
     * @return bool
     */
    public function isInvalid(): bool
    {
        return (bool)$this->getData('invalid');
    }

    /**
     * @param bool $invalid
     *
     * @return $this
     */
    public function setInvalid(bool $invalid): self
    {
        $this->setData('invalid', $invalid);

        return $this;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
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
        if ($this->flatScheme === null) {
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
        if ($this->groups === null) {
            $groups = $this->productTypeHelper->getProductTypeGroups(
                $this->getMarketplaceId(),
                $this->getNick()
            );

            $this->groups = array_combine(
                array_column($groups, 'nick'),
                array_column($groups, 'title')
            );
        }

        return $this->groups[$groupNick] ?? '';
    }
}
