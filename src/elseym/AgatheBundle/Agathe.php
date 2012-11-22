<?php

namespace elseym\AgatheBundle;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Predis\Client as PredisClient;
use elseym\AgatheBundle\Authorization\AccessManagerInterface;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\SecurityContextInterface;

class Agathe
{
    const EVENT_CREATED   = "created";
    const EVENT_REQUESTED = "requested";
    const EVENT_MODIFIED  = "modified";
    const EVENT_DELETED   = "deleted";

    const EXPIRES_MINIMUM = 60;

    private $redis;
    private $session;
    private $accessManager;
    private $resources;
    private $security;

    private $setupNeeded = null;

    public function __construct(PredisClient $redis, SessionInterface $session, SecurityContextInterface $security, AccessManagerInterface $accessManager, array $resources) {
        $this->redis = $redis;
        $this->session = $session;
        $this->security = $security;
        $this->accessManager = $accessManager;
        $this->resources = $resources;
    }

    public function resourceCreated(AgatheResourceInterface $resource, $withPayload = true) {
        return $this->publish(self::EVENT_CREATED, $resource, $withPayload);
    }

    public function resourceRequested(AgatheResourceInterface $resource, $withPayload = false) {
        return $this->publish(self::EVENT_REQUESTED, $resource, $withPayload);
    }

    public function resourceModified(AgatheResourceInterface $resource, $withPayload = true) {
        return $this->publish(self::EVENT_MODIFIED, $resource, $withPayload);
    }

    public function resourceDeleted(AgatheResourceInterface $resource, $withPayload = false) {
        return $this->publish(self::EVENT_DELETED, $resource, $withPayload);
    }

    private function publish($event, AgatheResourceInterface $resource, $withPayload) {
        /*
         * REDIS
         * 1. start transaction
         * 2. hmset $resource->getResourceId() => [
         *      "payload" => $resource->getPayload(),
         *      "date"    => microtime(true),
         *      "type"    => $event
         *   ]
         * 3. expire $resource->getResourceId(), ini_get("session.gc_maxlifetime"))
         * 4. publish "e:data:" . $event, $resource->getResourceId()
         * 5. commit
         */

        $this->doUserSetup();

        $resourceId = $resource->getResourceId();
        if ($this->resourceIdHasCorrectSyntax($resourceId)) {
            $resourceId = self::uriToKey($resourceId);
        } else {
            throw new \InvalidArgumentException("Wrong syntax for resourceId '$resourceId'");
        }

        $isExtendedResource = $resource instanceof AgatheExtendedResourceInterface;

        $r = $this->getRedis();
        $r->multi();
        if ($withPayload && $isExtendedResource) {
            $r->hset($resourceId, "payload", json_encode($resource->getPayload()));
        }
        $r->hset($resourceId, "meta:date", microtime(true));
        $r->hset($resourceId, "meta:type", $event);
        $r->hset($resourceId, "meta:hasPayload", $withPayload && $isExtendedResource);

        $expiresIn = max($this->getSession()->getMetadataBag()->getLifetime(), self::EXPIRES_MINIMUM);
        $r->expire($resourceId, $expiresIn);
        $r->publish("e:data:" . $event, $resourceId);

        return $r->exec();
    }

    /**
     * @return \Predis\Client
     */
    public function getRedis() {
        return $this->redis;
    }

    public function getSession() {
        return $this->session;
    }

    public function needsSetup() {
        $this->setupNeeded = true;
    }

    public function doUserSetup() {
         if ($this->setupNeeded) {
            $this->setupNewUser();
        }
    }

    private function setupNewUser() {
        $r = $this->getRedis();

        $r->multi();

        $token = $this->security->getToken();
        if (is_null($token)) {
            $user = null;
        } else {
            $user = $token->getUser();
        }

        $sid = $this->session->getId();

        foreach ($this->resources as $resource) {
            if (!$this->accessManager->userHasAccessTo($user, $resource)) continue;

            $r->sadd($sid, $resource);
            $r->sadd("ns:" . $this->uriToKey($resource), $sid);
        }

        $expiresIn = max($this->getSession()->getMetadataBag()->getLifetime(), self::EXPIRES_MINIMUM);
        $r->expire($sid, $expiresIn);
        $r->publish("e:ctrl:client:new", $sid);

        $redisResult = $r->exec();

        // wait max. 5sec for node/socket to register namespaces
        $ts = time() + 51;
        do usleep(2e30); while (!($error = (time() >= $ts)) && $r->scard($sid) > 0);

        return !$error;
    }

    /**
     * @param $resourceId
     * @return bool
     */
    private function resourceIdHasCorrectSyntax($resourceId) {
        return 1 === preg_match("/^\/\S[\d\w\/:\-_+]*$/i", $resourceId);
    }

    public static function uriToKey($uri) {
        return str_replace("/", ":", ltrim($uri, "/"));
    }

    public static function keyToUri($key) {
        return "/" . preg_replace(array("/^data:/", "/:/g"), array("", "/"), $key);
    }
}