<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VeiculoController extends Controller
{

    public function detalhe(Request $request, $id)
    {
        if (!$id) {
            return [];
        }

        $crawler = new CrawlerController();

        return $crawler->pesquisarDetalheVeiculos($request, $id);
    }
}
