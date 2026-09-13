<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

class Kindle implements EBook
{
    private int $page = 1;
    private int $totalPages = 100;

    public function pressNext(): void
    {
        $this->page++;
    }

    public function unlock(): void
    {
    }

    /**
     * @return int[]
     */
    public function getPage(): array
    {
        return [$this->page, $this->totalPages];
    }
}
