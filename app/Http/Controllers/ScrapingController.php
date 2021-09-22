<?php

namespace App\Http\Controllers;

use App\Models\Establishment;
use Goutte\Client;
use Illuminate\Http\Request;

class ScrapingController extends Controller
{
    
    protected $establishments;

    public function example (Client $client)
    {       

        // Ver cuantas páginas tiene que recorrer el scraping
        $pageUrl = 'https://www.paginasamarillas.com.co/medellin/servicios/ferreterias';

        $crawler = $client->request('GET', $pageUrl);

        $crawler->filter('ul.pagination')->each(function ($node) {
            $this->lastPage = $node->filter('li:nth-child(8)')->text();
            // echo $this->lastPage;
        });

        // dd($this->lastPage);

        // for($i=1; $i <= $this->lastPage; $i++){
        for($i=1; $i <= 4; $i++){
            $pageUrl = 'https://www.paginasamarillas.com.co/medellin/servicios/ferreterias?page='.$i;

            $crawler = $client->request('GET', $pageUrl);
            $this->extractContactsFrom($crawler);
        }

        // Para descargarlo en CSV
        // return $this->startDownloadCSV();

        // Para guardarlo en una BD
        // Se debe hacer la migración create_establishments_table
        return $this->savetoDB();

    }

    public function extractContactsFrom($crawler) {

        // :nth-child = En este caso como hay 2 <span> juntos con la misma clase
        // se cuenta el número de span, siendo el 2 el span del nombre y el 1 del index
        // del establecimiento
        // <span> (1) = 1
        // <span> (2) = Sapolin
        // $crawler = $client->request('GET', 'https://www.paginasamarillas.com.co/medellin/servicios/ferreterias');
        $crawler->filter('div.figBox > div.row')->each(function ($node) {

            $establishment['name'] = $node->filter('div.titleFig > a > span:nth-child(2)')->text();
            $location = explode( " - ", $node->filter('div.titleFig > span')->text() );
            $establishment['city'] = $location[0];
            $establishment['department'] = $location[1];

            // Dentro del contenedor seleccionado, se puede saltar entre contenedores
            // <span class="directionFig"> no hace parte del filtro padre
            // <div class="figbox"> luego <div class="row">
            // para llegar a directionFig este hace parte de un row, pero al seleccionar directamente directionFig
            // No hay que realizar $node->filter('div.row > div.col-xs-6 > div > span.directionFig')
            $establishment['address'] = $node->filter('span.directionFig')->text();

            if( $node->filter('span.phoneFig')->count() > 0 ) {
                $establishment['phone'] = $node->filter('span.phoneFig')->text();
            }else{
                $establishment['phone'] = null;
            }
            
            
            // Condición si el nodo contiene un resultado se crea un link con el contenido del nodo en la variable
            // sino se encuentra, se setea la variable en null para que al mostrar la información no muestre error 
            if ($node->filter('div.url > a')->count() > 0 ) {
                $establishment['webpage'] = $node->filter('div.url > a')->attr('href');
            }else{
                $establishment['webpage'] = null;
            }

            if( $node->filter('div.row > div.slogan')->count() > 0 ) {
                $establishment['description'] = $node->filter('div.row > div.infoBox')->last()->text(); 
            }else{
                $establishment['description'] = $node->filter('div.row > div.infoBox')->text();
            }


            if(strlen($establishment['description']) == 0) {
                $establishment['description'] = null;
                // dd( $establishment['name']. ' ' .strlen($establishment['description']) );
            }

            // $description = $node->filter('div.row > div.infoBox')->text();

            // echo "<strong>Nombre: </strong>". $establishment['name'] . '<br />';
            // echo "<strong>Ciudad: </strong>". $location[0] . '<br />';
            // echo "<strong>Departamento: </strong>". $location[1] . '<br />';
            // echo "<strong>Dirección: </strong>". $address . '<br />';
            // echo "<strong>Teléfono: </strong>". $phone . '<br />';
            // echo "<strong>Página web: </strong>". $webpage . '<br />';
            // echo "<strong>Descripción: </strong>". $description . '<br />';
            // echo "<hr>";

            // var_dump($establishment);

            $this->establishments[] = $establishment;
            
        });
        // dd($contact->html());

    }

    public function startDownloadCSV()
    {

        // // Crear archivo CSV
        // $fileName = date('d-m-Y').'.csv';
        // $file = fopen($fileName, 'w');

        // // Llenar archivo
        // $establishments = [];
        
        // $establishment1['name'] = 'Ferreteria 1';
        // $establishment1['phone'] = '+(57) 123 456';
        // $establishments[] = $establishment1;

        // $establishment2['name'] = 'Ferreteria 2';
        // $establishment2['phone'] = '+(57) 987 654';
        // $establishments[] = $establishment2;

        // foreach($establishments as $establishment) {
        //     fputcsv($file, $establishment);
        // }

        // // Generar descarga
        // return response()->download($fileName);


        
        // Crear archivo CSV
        $fileName = date('d-m-Y').'.csv';
        header('Content-Encoding: utf-8'); 
        header('Content-type: text/csv; charset=UTF-8'); 
        header('Content-Disposition: attachment; filename='.$fileName.''); 
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        $file = fopen($fileName, 'w');

        // Llenar archivo

            // Crear encabezados
        $headers = ['Nombre,Ciudad,Departamento,Dirección,Teléfono,Página web,Descripción'];
        fputcsv($file, $headers);
        

        foreach($this->establishments as $establishment) {
            fputcsv($file, $establishment);
        }


        // Generar descarga
        return response()->download($fileName);

    }

    public function savetoDB()
    {

        foreach($this->establishments as $establishment) {
            $newEstablishment = new Establishment();
            $newEstablishment->name = $establishment['name'];
            $newEstablishment->city = $establishment['city'];
            $newEstablishment->department = $establishment['department'];
            $newEstablishment->address = $establishment['address'];
            $newEstablishment->phone = $establishment['phone'];
            $newEstablishment->webpage = $establishment['webpage'];
            $newEstablishment->description = $establishment['description'];
            $newEstablishment->save();
        }

    }

}
