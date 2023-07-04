<?php

namespace Ess\M2ePro\Api\Exception;

class NotFoundException extends ApiException
{
    public function __construct(
        string $message,
        array $details = []
    ) {
        $phrase = new \Magento\Framework\Phrase($message);

        parent::__construct(
            $phrase,
            0,
            self::HTTP_NOT_FOUND,
            $details
        );
    }
}
