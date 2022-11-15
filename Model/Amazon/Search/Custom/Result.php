<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Custom;

class Result
{
    public const SUCCESS_STATUS = 0;
    public const FAIL_STATUS = 1;
    public const UNRESOLVED_IDENTIFIER_STATUS = 2;
    public const IDENTIFIER_NOT_FOUND_STATUS = 4;

    /** @var \Ess\M2ePro\Model\Amazon\Search\Custom\Query */
    private $query;
    /** @var array */
    private $responseData = [];

    /** @var int */
    private $status = self::SUCCESS_STATUS;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Search\Custom\Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->query->getIdentifierType();
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->query->getValue();
    }

    /**
     * @param array $responseData
     *
     * @return $this
     */
    public function setResponseData(array $responseData): self
    {
        $this->responseData = $responseData;
        return $this;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @param int $status
     *
     * @return Result
     */
    public function setStatus(int $status): Result
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::FAIL_STATUS;
    }

    /**
     * @return bool
     */
    public function isIdentifierUnresolved(): bool
    {
        return $this->status === self::UNRESOLVED_IDENTIFIER_STATUS;
    }

    /**
     * @return bool
     */
    public function isIdentifierNotFound(): bool
    {
        return $this->status === self::IDENTIFIER_NOT_FOUND_STATUS;
    }
}
