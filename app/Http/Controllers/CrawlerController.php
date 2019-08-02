<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class CrawlerController extends Controller
{
    const URL_SEMINOVOS_BUSCA_MARCA = 'https://www.seminovosbh.com.br/resultadobusca/index/veiculo/carro/estado-conservacao/seminovo%susuario/todos/pagina/%s';

    const URL_SEMINOVOS_BUSCA_AUTOMOVEL = 'https://www.seminovosbh.com.br/comprar////%s';

    public function pesquisarVeiculos(Request $request)
    {
        $url = $this->montarUrl($request);
        $pagina = $request->has('pagina') ? $request->get('pagina') : 1;

        $caminho = sprintf(self::URL_SEMINOVOS_BUSCA_MARCA, $url, $pagina);
        $errors = array();
        $result = $this->retornarDadosDaRequisicao($caminho, $errors);
        if (empty($errors)) {
            $response['content'] = $this->montarDadosDeRetorno($result);
            $response['Status'] = 'SUCCESS';
            $response['Message'] = 'Successfully';
        } else {
            $response['Errors'] = $errors;
            $response['Status'] = 'ERROR';
            $response['Message'] = "Errors: " . implode('|', $errors);
        }
        return response()->json($response);
    }

    public function pesquisarDetalheVeiculos(Request $request, $id)
    {
        $caminho = sprintf(self::URL_SEMINOVOS_BUSCA_AUTOMOVEL, $id);
        $errors = array();
        $result = $this->retornarDadosDaRequisicao($caminho, $errors);

        if (empty($errors)) {
            $response['content'] = $this->montarDadosDeRetornoDetalhe($result);
            $response['Status'] = 'SUCCESS';
            $response['Message'] = 'Successfully';
        } else {
            $response['Errors'] = $errors;
            $response['Status'] = 'ERROR';
            $response['Message'] = "Errors: " . implode('|', $errors);
        }
        return response()->json($response);
    }

    private function montarUrl(Request $request)
    {

        $url = '';
        if ($request->has('marca')) {
            $url .= 'marca/' . $request->get('marca') . '/';
        }
        if ($request->has('cidade')) {
            $url .= 'cidade/' . $request->get('cidade') . '/';
        }
        if ($request->has('valorDe')) {
            $url .= 'valor1/' . $request->get('valor1') . '/';
        }
        if ($request->has('valorAte')) {
            $url .= 'valor2/' . $request->get('valor2') . '/';
        }
        if ($request->has('anoDe')) {
            $url .= 'ano1/' . $request->get('ano1') . '/';
        }
        if ($request->has('anoAte')) {
            $url .= 'ano2/' . $request->get('ano2') . '/';
        }
        if ($url != '') {
            $url = '/' . $url;
        } else {
            $url = '/';
        }

        return  $url;
    }

    private function retornarDadosDaRequisicao($url, $errors)
    {
        $client = new Client();
        $response = $client->get($url);

        if ($response->getStatusCode() == 200) {
            return (string) $response->getBody();
        } else {
            array_push($errors, $response->getReasonPhrase());
            return;
        }
    }

    private function montarDadosDeRetorno($result)
    {
        try {
            $crawler = new Crawler($result);
            $nomes = $crawler->filter('.bg-busca .titulo-busca')->each(function ($node) {
                $desc[] = trim($node->text());
                return $desc;
            });
            $links = $crawler->filter('.bg-busca > dt')->filterXPath('//a[contains(@href, "")]')->each(function ($node) {
                return $elemento[] = $node->extract(['href'])[0];
            });
            $linksDetalhes = [];
            foreach ($links as $key => $value) {
                if (substr($value, 0, 15) != '/veiculo/codigo') {
                    $linksDetalhes[] = $value;
                }
            }
            $retorno = [];
            foreach ($nomes as $key => $value) {
                $retorno[] = array(
                    'nome' => $value[0],
                    'url' => $linksDetalhes[$key]
                );
            }
            return $retorno;
        } catch (Exception $ex) { }
    }

    private function montarDadosDeRetornoDetalhe($result)
    {
        try {
            $crawler = new Crawler($result);
            $imagensVeiculo = $this->imagensDetalhe($crawler);
            $nomeAnuncio = $crawler->filter('#textoBoxVeiculo > h5')->each(function ($node) {
                return trim($node->text());
            });
            $valorVeiculo = $crawler->filter('#textoBoxVeiculo > p')->each(function ($node) {
                return trim($node->text());
            });
            $detalhes = $crawler->filter('#infDetalhes > span > ul > li')->each(function ($node) {
                return trim($node->text());
            });
            $acessorios = $crawler->filter('#infDetalhes2 > ul > li')->each(function ($node) {
                return trim($node->text());
            });
            $observacoes = $crawler->filter('#infDetalhes3 > ul > p')->each(function ($node) {
                return trim($node->text());
            });
            $contato = $crawler->filter('#infDetalhes4 .texto> ul > li')->each(function ($node) {
                return trim($node->text());
            });

            return array(
                'anuncio' => $nomeAnuncio[0],
                'valorVeiculo' => $valorVeiculo[0],
                'detalhes' => $detalhes,
                'acessorios' =>$acessorios,
                'observacoes' => $observacoes[0],
                'contato' => $contato
            );
        } catch (Exception $ex) { }
    }

    private function imagensDetalhe(Crawler $crawler)
    {
        $imagemPrincipal = $crawler->filter('#fotoVeiculo')->filterXPath('//img[contains(@src, "")]')->each(function ($node) {
            return $node->extract(['src'])[0];
        });
        $imagens = $crawler->filter('#conteudoVeiculo')->filterXPath('//img[contains(@src, "")]')->each(function ($node) {
            return $node->extract(['src'])[0];
        });
        $imagensVeiculo = [];
        foreach ($imagens as $key => $value) {
            if (strpos($value, 'photoNone.jpg') === false) {
                $imagensVeiculo[] = $value;
            }
        }
        return array_merge($imagemPrincipal, $imagensVeiculo);
    }
}
