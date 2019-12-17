<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->servicesDir,
        $config->application->libraryDir,
    ], true
)->registerNamespaces(
    [
        "Library" => $config->application->libraryDir,
        "Controllers" => $config->application->controllersDir,
        "Services" => $config->application->servicesDir,
    ]
)->register();
