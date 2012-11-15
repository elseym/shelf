<?php

namespace elseym\AgatheBundle;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Predis\Client as PredisClient;

class Agathe
{
    const EVENT_CREATED   = "created";
    const EVENT_REQUESTED = "requested";
    const EVENT_MODIFIED  = "modified";
    const EVENT_DELETED   = "deleted";

    const EXPIRES_MINIMUM = 60;

    private $redis;
    private $session;

    public function __construct(PredisClient $redis, SessionInterface $session) {
        $this->redis = $redis;
        $this->session = $session;
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