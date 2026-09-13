# Adapter na prática: fazendo um Kindle passar por livro

Atividade de Padrões de Projeto (estruturais), feita em dupla, em cima do exemplo
`Structural/Adapter` do repositório [DesignPatternsPHP](https://github.com/DesignPatternsPHP/DesignPatternsPHP).

A ideia era simples de enunciar e menos simples de resolver sem fazer gambiarra: o código
cliente só sabe conversar com a interface `Book`, e o `Kindle` fala outra língua. Ninguém
pode mexer no `Kindle` (é código de terceiros) e mexer em `Book` quebraria o `PaperBook` e
todo mundo que já usa a interface. Sobra o meio de campo, que é onde entra o Adapter.

**Dupla:** _(preencher)_ e _(preencher)_
**Arquivo pedido pela atividade:** `Structural/Adapter/EBookAdapter.php`

---

## O que tem dentro

```
.
├── composer.json
├── phpunit.xml
├── .gitignore
├── README.md
└── Structural/
    └── Adapter/
        ├── Book.php              <- Target (veio do repo base, não mexemos)
        ├── PaperBook.php         <- implementação padrão (repo base)
        ├── EBook.php             <- interface do subsistema externo (repo base)
        ├── Kindle.php            <- Adaptee concreto (repo base)
        ├── EBookAdapter.php      <- ISSO AQUI é o que nós escrevemos
        ├── exemplo.php           <- demonstração que roda sem composer
        └── Tests/
            └── EBookAdapterTest.php
```

Os quatro primeiros arquivos são cópias fiéis do repositório original, só para o projeto
rodar sozinho. Nosso trabalho é o `EBookAdapter.php`, mais o exemplo e os testes.

## Rodando

Só para ver funcionando, sem instalar nada além do PHP 8.1+:

```bash
php Structural/Adapter/exemplo.php
```

Saída que a gente obteve aqui (PHP 8.3.6):

```
--- mesma funcao cliente, dois tipos de livro ---
PaperBook    parou na pagina 3
EBookAdapter parou na pagina 3

--- conferindo o contrato ---
EBookAdapter e um Book? sim
Total de paginas do Kindle (dado extra do adaptee): 100
```

Para os testes, aí sim precisa do Composer:

```bash
composer install
./vendor/bin/phpunit
```

## Como chegamos aqui

Primeiro clonamos o repositório base e instalamos as dependências:

```bash
git clone https://github.com/DesignPatternsPHP/DesignPatternsPHP.git
cd DesignPatternsPHP
composer install
```

Depois fomos ler `Structural/Adapter` arquivo por arquivo, antes de escrever qualquer coisa.
O elenco é esse:

- `Book` é a interface que o cliente conhece: `open()`, `turnPage()` e `getPage(): int`.
- `PaperBook` implementa `Book` direto, sem drama. Começa na página 1 e vai incrementando.
- `EBook` é a interface do aparelho de terceiros. Mesma ideia, nomes diferentes: `unlock()`,
  `pressNext()` e um `getPage()` que devolve **array**.
- `Kindle` implementa `EBook`. O `getPage()` dele retorna `[$paginaAtual, $totalDePaginas]`,
  tipo `[1, 100]`.

### O conflito, na marra

Escrevemos uma função cliente bem boba tipando o parâmetro como `Book` e passamos um `Kindle`
para ver o que acontecia. O PHP não perdoa:

```
PHP Fatal error:  Uncaught TypeError: lerCapitulo(): Argument #1 ($livro) must be of type
DesignPatterns\Structural\Adapter\Book, DesignPatterns\Structural\Adapter\Kindle given
```

São dois problemas empilhados, não um:

1. **Os nomes não batem.** `open()` x `unlock()`, `turnPage()` x `pressNext()`.
2. **O tipo de retorno não bate.** `Book::getPage()` promete `int`, `EBook::getPage()` entrega
   `int[]`. Mesmo que os nomes batessem, PHP com `declare(strict_types=1)` recusaria.

### O que a gente cogitou e descartou

Antes de cair no Adapter, discutimos duas saídas que pareciam mais rápidas:

- **Mudar a interface `Book`** para aceitar array. Resolveria em dois minutos e quebraria o
  `PaperBook` e todo cliente existente. Viola o princípio aberto/fechado na cara.
- **Fazer o adapter estender `Kindle`** (class adapter, via herança). Além de PHP não ter
  herança múltipla, isso amarraria o adapter a uma marca específica de leitor. Se aparecer um
  Kobo depois, começa tudo de novo.

Ficamos com o **object adapter**: uma classe que implementa `Book` e guarda um `EBook` dentro.

## A classe

`Structural/Adapter/EBookAdapter.php`, resumida:

```php
final class EBookAdapter implements Book
{
    private EBook $leitorDigital;

    public function __construct(EBook $leitorDigital)
    {
        $this->leitorDigital = $leitorDigital;
    }

    public function open(): void
    {
        $this->leitorDigital->unlock();
    }

    public function turnPage(): void
    {
        $this->leitorDigital->pressNext();
    }

    public function getPage(): int
    {
        [$paginaAtual] = $this->leitorDigital->getPage();

        return $paginaAtual;
    }
}
```

Três decisões que vale explicar, porque não foram automáticas:

**O construtor recebe `EBook`, não `Kindle`.** Assim o adapter serve para qualquer aparelho
que implemente a interface do subsistema externo. É a inversão de dependência do SOLID
aplicada no lugar mais óbvio possível.

**Composição, não herança.** O adaptee entra como atributo. O adapter não "é um" Kindle, ele
"tem um" Kindle e traduz recados. Isso também deixou os testes fáceis: dá para injetar um mock
de `EBook` e verificar se as chamadas caem nos métodos certos.

**O `getPage()` descarta informação de propósito.** O destructuring `[$paginaAtual] = ...` pega
só o índice 0. O contrato `Book` não tem onde encaixar o total de páginas, e não é papel do
adapter inventar contrato novo. Como o dado existe e seria desperdício, deixamos um
`getTotalPages()` **fora** da interface: quem programa contra `Book` nunca vai enxergar esse
método, então o padrão continua limpo.

## Testes

Cinco casos em `Structural/Adapter/Tests/EBookAdapterTest.php`:

- `PaperBook` continua funcionando como antes (nada foi quebrado);
- o Kindle adaptado e o livro de papel param na mesma página depois das mesmas chamadas;
- o adapter realmente é um `Book` (`assertInstanceOf`);
- com um mock de `EBook`, conferimos que `open()` chamou `unlock()` uma vez e `turnPage()`
  chamou `pressNext()` duas vezes — esse é o teste que prova a tradução em si;
- `getTotalPages()` lê o índice 1 do array do adaptee.

O teste do mock é o mais importante dos cinco. Os outros verificam resultado; esse verifica
que a delegação está acontecendo do jeito certo, e quebraria se alguém trocasse a composição
por alguma gambiarra que devolve o número certo por acaso.

## Versionando

```bash
git checkout -b feature/ebook-adapter
git add Structural/Adapter/EBookAdapter.php Structural/Adapter/Tests/ README.md
git commit -m "Adiciona EBookAdapter traduzindo EBook para o contrato Book"
git remote add origin https://github.com/USUARIO/REPOSITORIO.git
git push -u origin feature/ebook-adapter
```

## Fechando

O Adapter não deixa o código mais bonito por si só — ele concentra a feiura num lugar só. A
incompatibilidade entre `Book` e `EBook` não sumiu, ela mora inteira dentro de uma classe de
pouco mais de 40 linhas, e o resto do sistema segue achando que todo livro se abre com
`open()`. Quando chegar o próximo aparelho com API esquisita, o custo é um arquivo novo, e
nenhuma linha alterada no que já funciona.
