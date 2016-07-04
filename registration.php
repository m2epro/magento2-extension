<?php

use Ess\M2ePro\Helper\Module;
use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, Module::IDENTIFIER, __DIR__);