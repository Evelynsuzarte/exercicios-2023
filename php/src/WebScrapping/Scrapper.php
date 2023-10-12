<?php

namespace Chuva\Php\WebScrapping;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Chuva\Php\WebScrapping\Entity\Paper;
use Chuva\Php\WebScrapping\Entity\Person;

libxml_use_internal_errors(TRUE);

/**
 * Does the scrapping of a webpage.
 */
class Scrapper {

  /**
   * Variavel para uso do DOM.
   *
   * @var 
   */
  private $item;

  /**
   * Declaração de variavel de quantidade de autor por obra.
   * @var array
   */
  public $nAutorObra = [];

  /**
   * Declaração de variavel de lista de obras.
   * @var array
   */
  public $listaObras = [];

  /**
   * Loads paper information from the HTML and returns the array with the data.
   */
  public function buscar(\DOMDocument $dom) {
    // Declaração de variáveis.
    $titulos = $tipos = $id = $autores = $instituicao = $lista_autores = [];
    $aux = $nova_obra = NULL;

    $xpath = new \DOMXPath($dom);

    // Busca de id.
    $xpath_busca_id = $xpath->query('.//div[@class="volume-info"]');
    foreach ($xpath_busca_id as $item) {
      array_push($id, $item->textContent);
    }

    // Busca de títulos.
    $xpath_busca_titulos = $xpath->query('.//h4[@class="my-xs paper-title"]');
    foreach ($xpath_busca_titulos as $item) {
      array_push($titulos, $item->textContent);
    }

    // Busca de tipos.
    $xpath_busca_tipo = $xpath->query('.//div[@class="tags mr-sm"]');
    foreach ($xpath_busca_tipo as $item) {
      array_push($tipos, $item->textContent);
    }

    // Busca de instituição.
    $xpath_busca_instituicao = $xpath->query('//div[@class="authors"]/span[@title]');
    foreach ($xpath_busca_instituicao as $item) {
      array_push($instituicao, $item->getAttribute('title'));
    }

    // Busca de atores.
    $xpath_busca_autor = $xpath->query('.//div[@class="authors"]');
    foreach ($xpath_busca_autor as $item) {
      $partes = explode(";", $item->textContent);
      for ($i = 0; $i < count($partes) - 1; $i++) {
        array_push($autores, $partes[$i]);
      }
      array_push($this->nAutorObra, count($partes) - 1);
      $partes = [];
    }

    // Criação de objetos.
    $ultimo = $j = $somador = 0;
    $quantidade_obras = count($id);
    for ($i = 0; $i < $quantidade_obras; $i++) {
      $quant_autores = (int) $this->nAutorObra[$i];
      for ($j = 0; $j < $quant_autores; $j++) {
        $aux = new Person($autores[$j + $ultimo], $instituicao[$j + $ultimo]);
        array_push($lista_autores, $aux);
        $somador = $somador + 1;
      }
      $ultimo = $somador;

      $nova_obra = new Paper($id[$i], $titulos[$i], $tipos[$i], $lista_autores);
      array_push($this->listaObras, $nova_obra);
      $lista_autores = [];
    }
    return $this->listaObras;
  }

  /**
   * Método para buscar a quantidade máxima de autores que pode ter em uma obra.
   */
  public function maiorQuantidadeAutores() {
    $maior = $this->nAutorObra[0];
    for ($i = 0; $i < count($this->nAutorObra); $i++) {
      if ($this->nAutorObra[$i] > $maior) {
        $maior = $this->nAutorObra[$i];
      }
    }
    return $maior;
  }

  /**
   * Método para escrever arquivo com os dados.
   */
  public function escreverArquivo() {
    $lista = [];
    $escrever = WriterEntityFactory::createXLSXWriter();
    $escrever->openToFile(__DIR__ . 'planilha.xlsx');

    $cells = ['ID', 'Title', 'Type'];

    // Criação de colunas de autores.
    $quantidade = Scrapper::maiorQuantidadeAutores();
    for ($i = 0; $i < $quantidade; $i++) {
      $nova_string = "Author " . $i + 1;
      $nova_string2 = "Author " . $i + 1 . " - Institution";
      array_push($cells, $nova_string);
      array_push($cells, $nova_string2);
    }

    $linha = WriterEntityFactory::createRowFromArray($cells);
    $escrever->addRow($linha);

    // Preenchimento de dados nas colunas.
    foreach ($this->listaObras as $elemento) {
      $lista = $elemento->authors;
      $cells = [$elemento->id, $elemento->title, $elemento->type];
      for ($i = 0; $i < count($elemento->authors); $i++) {
        $nome = $elemento->authors[$i]->name;
        $institu = $elemento->authors[$i]->institution;
        array_push($cells, $nome);
        array_push($cells, $institu);
      }
      $linha = WriterEntityFactory::createRowFromArray($cells);
      $escrever->addRow($linha);
      $cells = [];
    }

    $escrever->close();
  }

}
