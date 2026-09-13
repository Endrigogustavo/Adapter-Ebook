<?php

declare(strict_types=1);

namespace DesignPatterns\Structural\Adapter\Tests;

use DesignPatterns\Structural\Adapter\Book;
use DesignPatterns\Structural\Adapter\EBook;
use DesignPatterns\Structural\Adapter\EBookAdapter;
use DesignPatterns\Structural\Adapter\Kindle;
use DesignPatterns\Structural\Adapter\PaperBook;
use PHPUnit\Framework\TestCase;

class EBookAdapterTest extends TestCase
{
    public function testLivroDePapelContinuaFuncionandoIgual(): void
    {
        $livro = new PaperBook();
        $livro->open();
        $livro->turnPage();

        self::assertSame(2, $livro->getPage());
    }

    public function testKindleAdaptadoSeComportaComoLivroDePapel(): void
    {
        $papel = new PaperBook();
        $tela = new EBookAdapter(new Kindle());

        foreach ([$papel, $tela] as $livro) {
            $livro->open();
            $livro->turnPage();
            $livro->turnPage();
        }

        self::assertSame($papel->getPage(), $tela->getPage());
    }

    public function testAdapterCumpreOContratoEsperadoPeloCliente(): void
    {
        self::assertInstanceOf(Book::class, new EBookAdapter(new Kindle()));
    }

    /**
     * O ponto central da tradução: garantir que as chamadas do Target caem
     * exatamente nos métodos do Adaptee, com os nomes dele.
     */
    public function testChamadasSaoDelegadasAosMetodosDoAdaptee(): void
    {
        $falso = $this->createMock(EBook::class);
        $falso->expects(self::once())->method('unlock');
        $falso->expects(self::exactly(2))->method('pressNext');
        $falso->method('getPage')->willReturn([7, 250]);

        $livro = new EBookAdapter($falso);
        $livro->open();
        $livro->turnPage();
        $livro->turnPage();

        self::assertSame(7, $livro->getPage());
    }

    public function testTotalDePaginasVemDoSegundoIndiceDoAdaptee(): void
    {
        $falso = $this->createMock(EBook::class);
        $falso->method('getPage')->willReturn([7, 250]);

        self::assertSame(250, (new EBookAdapter($falso))->getTotalPages());
    }
}
