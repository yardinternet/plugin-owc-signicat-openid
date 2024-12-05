<?php

namespace OWCSignicatOpenID;

use Psr\Container\ContainerInterface;
use RuntimeException;

class ContainerManager
{
    private static ?ContainerInterface $container = null;

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (null === self::$container) {
            throw new RuntimeException('DI container is not initialized.');
        }

        return self::$container;
    }
}
