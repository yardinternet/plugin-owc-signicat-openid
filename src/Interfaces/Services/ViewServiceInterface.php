<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Interfaces\Services;

interface ViewServiceInterface extends ServiceInterface
{

    public function render(string $template, array $args = []): string;

}
