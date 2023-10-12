<?php

namespace Chuva\Php\WebScrapping;

use Chuva\Php\WebScrapping\Entity\Paper;
use Chuva\Php\WebScrapping\Entity\Person;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Type;


libxml_use_internal_errors(true);
/**
 * Does the scrapping of a webpage.
 */
class Scrapper {

    /** @var DOMNode*/
    private $item;

    public $n_autor_obra = [];
    public $lista_obras = [];

  /**
   * Loads paper information from the HTML and returns the array with the data.
   */
  public function buscar(\DOMDocument $dom){
    
    //declaração de variáveis
    $titulos = $tipos = $id = $autores = $instituicao = $lista_autores = [];
    $aux = $nova_obra = null;
    
    $xpath = new \DOMXPath($dom);

    //busca de id
    $xpath_busca_id=$xpath->query('.//div[@class="volume-info"]');
    foreach ($xpath_busca_id as $item){
      array_push($id,$item->textContent);
    }

    //busca de títulos
    $xpath_busca_titulos= $xpath->query('.//h4[@class="my-xs paper-title"]');
    foreach ($xpath_busca_titulos as $item){
      array_push($titulos,$item->textContent);                           //adiciona os livros em uma lista
    }

    //busca de tipos
    $xpath_busca_tipo=$xpath->query('.//div[@class="tags mr-sm"]');
    foreach ($xpath_busca_tipo as $item){
      array_push($tipos,$item->textContent);
    }
 
    //busca de instituição
     $xpath_busca_instituicao = $xpath->query('//div[@class="authors"]/span[@title]');
     foreach ($xpath_busca_instituicao as $item){
      array_push($instituicao,$item->getAttribute('title'));
     }

    //busca de atores
    $xpath_busca_autor=$xpath->query('.//div[@class="authors"]');
    foreach ($xpath_busca_autor as $item){
      $partes = explode(";", $item->textContent);                                              //separa item por ;
      for ($i = 0; $i < count($partes)-1; $i++) {                                             //pega o vetor parcionado e adiciona os itens na lista geral
        array_push($autores,$partes[$i]);
      }
      array_push($this->n_autor_obra,count($partes)-1);     
      $partes = [];
    }

    //Criação de objetos
    $ultimo = $j = $somador = 0;                                                              //guarda ultima posição usada do vetor de nome de autores
    $quantidade_obras = count($id);                                                          //quantidade de obras
    for ($i = 0; $i < $quantidade_obras; $i++) {                                
      $quant_autores = (int)$this->n_autor_obra[$i];                                         //quantidade de vezes q o loop vai rodar, que vai ser de acordo com o numero de autores que cada obra tem
      for ($j = 0; $j < $quant_autores; $j++){                                  
          $aux = new Person($autores[$j+$ultimo], $instituicao[$j+$ultimo]);
          array_push($lista_autores, $aux);
          $somador = $somador + 1; 
      }
      $ultimo = $somador;

      $nova_obra = new Paper($id[$i], $titulos[$i], $tipos[$i], $lista_autores);
      array_push($this->lista_obras, $nova_obra);
      $lista_autores = [];
    }
    return $this->lista_obras;
  }

    /**
   * Método para buscar a quantidade máxima de autores que pode ter em uma obra.
   */
  public function maior_quantidade_autores(){
    $maior = $this->n_autor_obra[0];
    for ($i = 0; $i < count($this->n_autor_obra); $i++){
      if($this->n_autor_obra[$i] > $maior){
        $maior = $this->n_autor_obra[$i];
      }
    }
    return $maior;
  }

  /**
   * Método para escrever arquivo com os dados.
   */
  public function escreverArquivo(){
    $lista = [];
    $escrever = WriterEntityFactory::createXLSXWriter();
    $escrever->openToFile(__DIR__ .'planilha.xlsx');

    $cells = ['ID','Title','Type'];                                       //colunas
    
    //criação de colunas de autores
    $quantidade = Scrapper::maior_quantidade_autores();
    for ($i = 0; $i < $quantidade; $i++){
      $nova_string = "Author ". $i+1;
      $nova_string2 = "Author ". $i+1 ." - Institution";
      array_push($cells,$nova_string);
      array_push($cells,$nova_string2);
    }

    $linha = WriterEntityFactory::createRowFromArray($cells);          //cria linha
    $escrever->addRow($linha);                                        //adiciona linha
    
    //preenchimento de dados nas colunas
    foreach ($this->lista_obras as $elemento){
      $lista = $elemento->authors;
      $cells = [$elemento->id, $elemento->title, $elemento->type];
      for ($i = 0; $i < count($elemento->authors); $i++){
        $nome = $elemento->authors[$i]->name;
        $institu = $elemento->authors[$i]->institution;
        array_push($cells,$nome);
        array_push($cells,$institu);
      }
      $linha = WriterEntityFactory::createRowFromArray($cells);          //cria linha
      $escrever->addRow($linha);                                                      //adiciona linha
      $cells = [];
    }

    $escrever->close();
  }
}
