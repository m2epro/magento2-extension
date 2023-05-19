<?php

namespace Ess\M2ePro\Helper;

class Analytics
{
    public function getUrl(): string
    {
        return 'https://analytics.m2e.cloud';
    }

    public function getLoginUrl(): string
    {
        return $this->getUrl() . '/login';
    }
}
