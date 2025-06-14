<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'machine_name' => [
        'path' => './assets/easyadmin/field/machine_name/machine_name.js',
        'entrypoint' => true,
    ],
    'plugin_settings' => [
        'path' => './assets/easyadmin/field/plugin_settings/plugin_settings.js',
        'entrypoint' => true,
    ],
    'project_running' => [
        'path' => './assets/easyadmin/action/project_running/project_running.js',
        'entrypoint' => true,
    ],
    'bootstrap-extends' => [
        'path' => './assets/easyadmin/bootstrap_extends.js',
        'entrypoint' => true,
    ],
    'transliteration' => [
        'version' => '2.3.5',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    'chart.js' => [
        'version' => '4.4.9',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'bootstrap-table' => [
        'version' => '1.24.1',
    ],
    'bootstrap-table/dist/bootstrap-table.min.css' => [
        'version' => '1.24.1',
        'type' => 'css',
    ],
    'bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.min.js' => [
        'version' => '1.24.1',
    ],
];
