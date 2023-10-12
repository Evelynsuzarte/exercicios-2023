<?php

namespace Chuva\Php\WebScrapping;

use Chuva\Php\WebScrapping\Scrapper;

libxml_use_internal_errors(TRUE);

/**
 * Runner for the Webscrapping exercice.
 */
class Main {

  /**
   * Main runner, instantiates a Scrapper and runs.
   */
  public static function run(): void {
    $dom = new \DOMDocument('1.0', 'utf-8');
    $dom->loadHTMLFile(__DIR__ . '/../../assets/origin.html');

    $scrapper = (new Scrapper());
    print_r($scrapper->buscar($dom));
    $scrapper->escreverArquivo();
  }

}
