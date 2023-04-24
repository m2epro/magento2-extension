<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
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
}
