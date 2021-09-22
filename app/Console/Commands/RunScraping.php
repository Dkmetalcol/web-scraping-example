<?php

namespace App\Console\Commands;

use Goutte\Client;
use App\Models\Establishment;
use Illuminate\Console\Command;

class RunScraping extends Command
{
    protected $establishments;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:scraping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comienza a hacer scraping de los establecimientos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
       // Ver cuantas páginas tiene que recorrer el scraping
       $pageUrl = 'https://www.paginasamarillas.com.co/medellin/servicios/ferreterias';

       $crawler = $client->request('GET', $pageUrl);

       $crawler->filter('ul.pagination')->each(function ($node) {
           $this->lastPage = $node->filter('li:nth-child(8)')->text();
           // echo $this->lastPage;
       });


       for($i=1; $i <= $this->lastPage; $i++){
    //    for($i=1; $i <= 4; $i++){
           $pageUrl = 'https://www.paginasamarillas.com.co/medellin/servicios/ferreterias?page='.$i;

           $crawler = $client->request('GET', $pageUrl);
           $this->extractContactsFrom($crawler);
       }

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
            }

            $this->establishments[] = $establishment;
            
        });
        // dd($contact->html());

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
