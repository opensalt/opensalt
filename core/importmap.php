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
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    '@symfony/stimulus-bundle' => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@symfony/ux-autocomplete' => ['path' => './vendor/symfony/ux-autocomplete/assets/dist/controller.js'],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    '@opensalt/ob3-definer' => ['version' => '1.2.12', 'package_specifier' => '@opensalt/ob3-definer/dist/ob3-definer.js'],
    '@opensalt/ob3-definer/dist/ob3-definer.css' => ['version' => '1.2.12', 'type' => 'css'],
    'datatables.net' => ['path' => './vendor/datatables.net/datatables.net/js/dataTables.mjs'],
    'datatables.net-bs5' => ['path' => './vendor/datatables.net/datatables.net-bs5/js/dataTables.bootstrap5.mjs'],
    'app' => ['path' => './assets/app.js', 'entrypoint' => true],
    'session' => ['path' => './assets/js/site.js', 'entrypoint' => true],
    'admin' => ['path' => './assets/js/main.js', 'entrypoint' => true],
    'signup' => ['path' => './assets/js/signup.js', 'entrypoint' => true],
    'systemLogs' => ['path' => './assets/js/system-logs.js', 'entrypoint' => true],
    'importFramework' => ['path' => './assets/js/import-framework.js', 'entrypoint' => true],
    'importPage' => ['path' => './assets/js/import-page.js', 'entrypoint' => true],
    'credential' => ['path' => './assets/js/credential.js', 'entrypoint' => true],
    'tom-select' => ['version' => '2.6.2'],
    '@orchidjs/sifter' => ['version' => '1.1.0'],
    '@orchidjs/unicode-variants' => ['version' => '1.2.2'],
    'tom-select/dist/css/tom-select.default.min.css' => ['version' => '2.6.2', 'type' => 'css'],
    'tom-select/dist/css/tom-select.default.css' => ['version' => '2.6.2', 'type' => 'css'],
    'tom-select/dist/css/tom-select.bootstrap4.css' => ['version' => '2.6.2', 'type' => 'css'],
    'tom-select/dist/css/tom-select.bootstrap5.css' => ['version' => '2.6.2', 'type' => 'css'],
];
