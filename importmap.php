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
    'source_plugin' => [
        'path' => './assets/easyadmin/field/source_plugin/source_plugin.js',
        'entrypoint' => true,
    ],
    'transliteration' => [
        'version' => '2.3.5',
    ],
];
