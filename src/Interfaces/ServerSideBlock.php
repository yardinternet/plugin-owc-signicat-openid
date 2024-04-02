<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Interfaces;

interface ServerSideBlock extends Block
{
    public function render(): string;
}
