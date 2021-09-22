<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

## Ejemplo de Scraping usando la libreria Goutte

Se ha usado la versión 8.61.0 de Laravel

La libreria Goutte puede ser descargada desde el siguiente link

https://github.com/FriendsOfPHP/Goutte

Para poder instalarla simplemente hay que ejecutar

```
composer require fabpot/goutte
```

La página que se hará el scraping es

https://www.paginasamarillas.com.co/medellin/servicios/ferreterias

Los archivos clave son

- app\Http\Controllers\ScrapingController.php

Acá se ha agregado una función de ejemplo para llamarla desde una ruta definida en `routes\web.php` como `/scraping`

- app\Console\Commands\RunScraping.php

Se ha creado un comando

```
php artisan make:command RunScraping
```

Acá se guarda toda la lógica para poder realizar una tarea programada para guardar los datos del scraping en una BD usando el modelo y migración

```
app\Models\Establishment.php
```

```
database\migrations\2021_09_22_040014_create_establishments_table.php
```


- app\Console\Kernel.php

Se ha creado una tarea programada para ejecutar el comando anteriormente creado

```
        $schedule->command('run:scraping')                
                ->everyMinute()
                ->timezone('America/Bogota')
                ->description('Comienza a hacer scraping de los establecimientos');
```

La tarea se ejecutará cada minuto en la zona horaria de America/Bogota y con una descripción

Las tareas creadas se pueden ver con el comando

```
php artisan schedule:list
```

## Ejecutar la tarea

### De forma manual

Para ejecutar la tarea manualmente se ejecuta

```
php artisan schedule:run
```

### Para hacerlo según programación

Se debe ejecutar el daemon del schedule de Laravel con

```
php artisan schedule:work
```

Esto se hará según la programación que hemos configurado en el `app\Console\Kernel.php` este proceso no se debe detener si se quiere respetar la programación.

## Videos de guia
- [WEB SCRAPING USANDO EL PAQUETE GOUTTE](https://programacionymas.com/series/web-scraping-usando-el-paquete-goutte).
- [Scraping Web a la pág de CompuTrabajo usando PHP](https://www.youtube.com/watch?v=0kz-JHM88vM).
- [Como configurar un CRON JOB con LARAVEL 2021](https://www.youtube.com/watch?v=0uG0B5HqiuA).
