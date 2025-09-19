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
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-react' => [
        'path' => './vendor/symfony/ux-react/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    'react' => [
        'version' => '18.1.0',
    ],
    'react-dom' => [
        'version' => '18.3.1',
    ],
    'scheduler' => [
        'version' => '0.23.2',
    ],
    '@react-spring/web' => [
        'version' => '10.0.1',
    ],
    '@react-spring/core' => [
        'version' => '10.0.1',
    ],
    '@react-spring/shared' => [
        'version' => '10.0.1',
    ],
    '@react-spring/animated' => [
        'version' => '10.0.1',
    ],
    '@react-spring/types' => [
        'version' => '10.0.1',
    ],
    '@react-spring/rafz' => [
        'version' => '10.0.1',
    ],
    'react-dom/client' => [
        'version' => '18.3.1',
    ],
    'react-sortablejs' => [
        'version' => '6.1.4',
    ],
    'sortablejs' => [
        'version' => '1.15.6',
    ],
    'classnames' => [
        'version' => '2.3.1',
    ],
    'tiny-invariant' => [
        'version' => '1.2.0',
    ],
    'phaser' => [
        'version' => '3.90.0',
    ],
];
