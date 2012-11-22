<?php

namespace elseym\AgatheBundle\Authorization;

use \Symfony\Component\Security\Core\User\UserInterface;

class DenyingAccessManager implements AccessManagerInterface
{
    public function userHasAccessTo(UserInterface $user = null, $resource) {
        return false;
    }
}