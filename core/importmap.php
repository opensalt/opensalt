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
    '@symfony/ux-vue' => ['path' => './vendor/symfony/ux-vue/assets/dist/loader.js'],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    'vue' => ['version' => '3.5.35', 'package_specifier' => 'vue/dist/vue.esm-bundler.js'],
    '@vue/runtime-dom' => ['version' => '3.5.35'],
    '@vue/compiler-dom' => ['version' => '3.5.35'],
    '@vue/shared' => ['version' => '3.5.35'],
    '@vue/runtime-core' => ['version' => '3.5.35'],
    '@vue/compiler-core' => ['version' => '3.5.35'],
    '@vue/reactivity' => ['version' => '3.5.35'],
];
