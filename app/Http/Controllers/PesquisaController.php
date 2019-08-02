<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PesquisaController extends Controller
{
    public function pesquisa(Request $request)
    {
        $crawler = new CrawlerController();

        return $crawler->pesquisarVeiculos($request);
    }
}
