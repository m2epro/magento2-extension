<?php

namespace Ess\M2ePro\Model;

class TagFactory
{
    public function create(string $errorCode, string $text): Tag
    {
        return new Tag($errorCode, $text);
    }

    public function createWithHasErrorCode(): Tag
    {
        return $this->create(Tag::HAS_ERROR_ERROR_CODE, 'Has error');
    }
}
