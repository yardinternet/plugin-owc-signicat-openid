<?php

namespace OWCSignicatOpenID\Interfaces\Services;

/**
 * Exit when accessed directly.
 */
if (! defined('ABSPATH')) {
    exit;
}

interface EncryptionServiceInterface
{
    public function encrypt(string $content): string;

    public function decrypt(string $content): string;
}
