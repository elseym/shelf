<?php

namespace elseym\AgatheBundle\Authorization;

use \Symfony\Component\Security\Core\User\UserInterface;

class GrantingAccessManager implements AccessManagerInterface
{
    public function userHasAccessTo(UserInterface $user = null, $resource) {
        return true;
    }
}