<?php

namespace elseym\AgatheBundle;

use elseym\AgatheBundle\AgatheResourceInterface;

interface AgatheExtendedResourceInterface extends AgatheResourceInterface
{
    public function getPayload();
    public function getCommand();
}