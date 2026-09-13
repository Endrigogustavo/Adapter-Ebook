<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter;

/**
 * Faz um EBook (Kindle e afins) se passar por um Book comum.
 *
 * O cliente continua falando só a "língua" da interface Book. Quem fala a
 * língua do leitor digital é esta classe, e ninguém mais. Se amanhã aparecer
 * um Kobo com outra API, nasce outro adapter e nada do resto muda.
 *
 * Object Adapter: guardamos o adaptee como atributo (composição) em vez de
 * herdar dele. Herança aqui nem seria possível, porque EBook é justamente a
 * interface incompatível que queremos esconder.
 */
final class EBookAdapter implements Book
{
    private EBook $leitorDigital;

    public function __construct(EBook $leitorDigital)
    {
        // Repare no type hint: EBook, não Kindle. O adapter depende da
        // abstração do subsistema externo, não de uma implementação concreta.
        $this->leitorDigital = $leitorDigital;
    }

    public function open(): void
    {
        // "abrir o livro" no mundo do Kindle é destravar a tela
        $this->leitorDigital->unlock();
    }

    public function turnPage(): void
    {
        // "virar a página" vira apertar o botão de avançar
        $this->leitorDigital->pressNext();
    }

    public function getPage(): int
    {
        // Aqui mora a parte chata: o EBook devolve int[] no formato
        // [pagina_atual, total_de_paginas] e o contrato Book pede um int.
        // Pegamos só o primeiro item e engolimos o resto.
        [$paginaAtual] = $this->leitorDigital->getPage();

        return $paginaAtual;
    }

    /**
     * Extra (fora do contrato Book): o total de páginas existe no adaptee e
     * seria desperdício jogar fora. Quem programa contra Book nunca enxerga
     * este método, então o padrão continua respeitado.
     */
    public function getTotalPages(): int
    {
        return $this->leitorDigital->getPage()[1];
    }
}
