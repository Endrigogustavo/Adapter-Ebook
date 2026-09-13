<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

interface Book
{
    public function open(): void;

    public function turnPage(): void;

    public function getPage(): int;
}
