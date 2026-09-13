<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

/*
 * Demonstração rodável sem composer:
 *     php Structural/Adapter/exemplo.php
 *
 * Dentro do repositório completo do DesignPatternsPHP o autoload já resolve
 * essas classes e os requires abaixo podem ser apagados.
 */
foreach (['Book', 'PaperBook', 'EBook', 'Kindle', 'EBookAdapter'] as $classe) {
    require_once __DIR__ . '/' . $classe . '.php';
}

/**
 * Cliente. Só sabe que recebeu um Book. Não faz ideia se é papel ou tela.
 */
function lerAsTresPrimeirasPaginas(Book $livro): string
{
    $livro->open();
    $livro->turnPage();
    $livro->turnPage();

    return sprintf(
        '%-12s parou na pagina %d',
        (new \ReflectionClass($livro))->getShortName(),
        $livro->getPage()
    );
}

$naEstante = [
    new PaperBook(),
    new EBookAdapter(new Kindle()),
];

echo "--- mesma funcao cliente, dois tipos de livro ---\n";

foreach ($naEstante as $livro) {
    echo lerAsTresPrimeirasPaginas($livro), "\n";
}

$kindleAdaptado = new EBookAdapter(new Kindle());

echo "\n--- conferindo o contrato ---\n";
echo 'EBookAdapter e um Book? ', $kindleAdaptado instanceof Book ? 'sim' : 'nao', "\n";
echo 'Total de paginas do Kindle (dado extra do adaptee): ', $kindleAdaptado->getTotalPages(), "\n";
