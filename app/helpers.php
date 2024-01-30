<?php

use Symfony\Component\Finder\Finder;

/**
 * Load the drivers available in the app.
 *
 * @return array
 */
function load_drivers()
{
    $drivers = [];
    $finder = new Finder;

    foreach($finder->files()->in(__DIR__ . '/../app/Drivers') as $driver) {
        $class = "App\Drivers\\". $driver->getBasename('.php');
        $drivers[strtolower($driver->getBasename('.php'))] = $class;
    }

    return $drivers;
}