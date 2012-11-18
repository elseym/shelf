<?php

namespace elseym\AgatheBundle\Authorization;

use \Symfony\Component\Security\Core\User\UserInterface;

interface AccessManagerInterface
{
    public function userHasAccessTo(UserInterface $user = null, $resource);
}