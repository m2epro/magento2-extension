<?php

namespace Ess\M2ePro\Block\Adminhtml\Dashboard\Errors;

interface UrlStorageInterface
{
    public function getUrlForToday(): string;

    public function getUrlForYesterday(): string;

    public function getUrlFor2DaysAgo(): string;

    public function getUrlForTotal(): string;
}
