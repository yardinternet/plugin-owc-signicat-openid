<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Block;

abstract class OpenIDBlock
{
    abstract protected function getIDP(): string;

    public function getMetaDataDirectory(): string
    {
        return OWC_SIGNICAT_OPENID_DIR_PATH . '/dist/' . static::getIDP();
    }

    public function render(): string
    {
        return 'test';
    }
}
