<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Tag\TaggedValue;

const PROJECT_DIR = __DIR__.'/../../../../../../../../';

/** @var Composer\Autoload\ClassLoader */
$loader = require PROJECT_DIR.'/vendor/autoload.php';

$editorConfig = loadEditorConfig();

if (!empty($_POST)) {
    $currentValues = saveYaml($_POST, $editorConfig);
    exec('rm -rf '.PROJECT_DIR.'/var/cache/*');
}
else {
    $currentValues = loadCurrentValues();
}

renderEditor($editorConfig, $currentValues);

function loadEditorConfig()
{
    #exec("/usr/local/bin/php ".PROJECT_DIR."/vendor/bin/contao-console contao:config-editor", $output, $exitCode);
    $exitCode = 0;
    $output = ['{"contao":{"bundleName":"ContaoCoreBundle","bundleAlias":"contao","attributes":[],"type":"ArrayNode","required":false,"fields":{"csrf_cookie_prefix":{"attributes":[],"type":"ScalarNode","required":false,"default":"csrf_"},"csrf_token_name":{"attributes":[],"type":"ScalarNode","required":false,"default":"contao_csrf_token"},"encryption_key":{"attributes":[],"type":"ScalarNode","required":false,"default":"%kernel.secret%"},"error_level":{"attributes":{"info":"The error reporting level set when the framework is initialized. Defaults to E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED."},"type":"IntegerNode","required":false,"default":8183},"legacy_routing":{"attributes":{"info":"Disabling legacy routing allows to configure the URL prefix and suffix per root page. However, it might not be compatible with third-party extensions."},"type":"BooleanNode","required":false,"default":true},"localconfig":{"attributes":{"info":"Allows to set TL_CONFIG variables, overriding settings stored in localconfig.php. Changes in the Contao back end will not have any effect."},"type":"VariableNode","required":false},"locales":{"attributes":{"info":"Allows to configure which languages can be used in the Contao back end. Defaults to all languages for which a translation exists."},"type":"PrototypedArrayNode","required":false,"default":["en","sl","pl","sv","ja","it","cs","ru","pt","zh","sr","nl","de","fr","es","fa"],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}},"prepend_locale":{"attributes":{"info":"Whether or not to add the page language to the URL."},"type":"BooleanNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.10","message":"The URL prefix is configured per root page since Contao 4.10. Using this option requires legacy routing."},"default":false},"pretty_error_screens":{"attributes":{"info":"Show customizable, pretty error screens instead of the default PHP error messages."},"type":"BooleanNode","required":false,"default":false},"preview_script":{"attributes":{"info":"An optional entry point script that bypasses the front end cache for previewing changes (e.g. preview.php)."},"type":"ScalarNode","required":false,"default":""},"upload_path":{"attributes":{"info":"The folder used by the file manager."},"type":"ScalarNode","required":false,"default":"files"},"editable_files":{"attributes":[],"type":"ScalarNode","required":false,"default":"css,csv,html,ini,js,json,less,md,scss,svg,svgz,txt,xliff,xml,yml,yaml"},"url_suffix":{"attributes":[],"type":"ScalarNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.10","message":"The URL suffix is configured per root page since Contao 4.10. Using this option requires legacy routing."},"default":".html"},"web_dir":{"attributes":{"info":"Absolute path to the web directory. Defaults to %kernel.project_dir%\/public."},"type":"ScalarNode","required":false,"default":"\/Users\/martin\/Sites\/contao-4\/web"},"image":{"attributes":[],"type":"ArrayNode","required":false,"default":{"bypass_cache":false,"imagine_options":{"jpeg_quality":80,"jpeg_sampling_factors":[2,1,1],"interlace":"plane"},"imagine_service":null,"reject_large_uploads":false,"sizes":[],"target_dir":"\/Users\/martin\/Sites\/contao-4\/assets\/images","target_path":null,"valid_extensions":["jpg","jpeg","gif","png","tif","tiff","bmp","svg","svgz","webp"]},"fields":{"bypass_cache":{"attributes":{"info":"Bypass the image cache and always regenerate images when requested. This also disables deferred image resizing."},"type":"BooleanNode","required":false,"default":false},"imagine_options":{"attributes":[],"type":"ArrayNode","required":false,"default":{"jpeg_quality":80,"jpeg_sampling_factors":[2,1,1],"interlace":"plane"},"fields":{"jpeg_quality":{"attributes":[],"type":"IntegerNode","required":false,"default":80},"jpeg_sampling_factors":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":[2,1,1],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}},"png_compression_level":{"attributes":[],"type":"IntegerNode","required":false},"png_compression_filter":{"attributes":[],"type":"IntegerNode","required":false},"webp_quality":{"attributes":[],"type":"IntegerNode","required":false},"webp_lossless":{"attributes":[],"type":"BooleanNode","required":false},"interlace":{"attributes":[],"type":"ScalarNode","required":false,"default":"plane"}}},"imagine_service":{"attributes":{"info":"Contao automatically uses an Imagine service out of Gmagick, Imagick and Gd (in this order). Set a service ID here to override."},"type":"ScalarNode","required":false,"default":null},"reject_large_uploads":{"attributes":{"info":"Reject uploaded images exceeding the localconfig.gdMaxImgWidth and localconfig.gdMaxImgHeight dimensions."},"type":"BooleanNode","required":false,"default":false},"sizes":{"attributes":{"info":"Allows to define image sizes in the configuration file in addition to in the Contao back end. Use the special name \"_defaults\" to preset values for all sizes of the configuration file."},"type":"PrototypedArrayNode","required":false,"default":[],"key":"name","prototype":{"attributes":[],"type":"ArrayNode","required":false,"fields":{"width":{"attributes":[],"type":"IntegerNode","required":false},"height":{"attributes":[],"type":"IntegerNode","required":false},"resize_mode":{"attributes":[],"type":"EnumNode","required":false,"values":["crop","box","proportional"]},"zoom":{"attributes":[],"type":"IntegerNode","required":false},"css_class":{"attributes":[],"type":"ScalarNode","required":false},"lazy_loading":{"attributes":[],"type":"BooleanNode","required":false},"densities":{"attributes":[],"type":"ScalarNode","required":false},"sizes":{"attributes":[],"type":"ScalarNode","required":false},"skip_if_dimensions_match":{"attributes":{"info":"If the output dimensions match the source dimensions, the image will not be processed. Instead, the original file will be used."},"type":"BooleanNode","required":false},"formats":{"attributes":{"info":"Allows to convert one image format to another or to provide additional image formats for an image (e.g. WebP).","example":{"jpg":["webp","jpg"],"gif":["png"]}},"type":"PrototypedArrayNode","required":false,"default":[],"key":"source","prototype":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}}},"items":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ArrayNode","required":false,"fields":{"width":{"attributes":[],"type":"IntegerNode","required":false},"height":{"attributes":[],"type":"IntegerNode","required":false},"resize_mode":{"attributes":[],"type":"EnumNode","required":false,"values":["crop","box","proportional"]},"zoom":{"attributes":[],"type":"IntegerNode","required":false},"media":{"attributes":[],"type":"ScalarNode","required":false},"densities":{"attributes":[],"type":"ScalarNode","required":false},"sizes":{"attributes":[],"type":"ScalarNode","required":false},"resizeMode":{"attributes":[],"type":"EnumNode","required":false,"values":["crop","box","proportional"],"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Using contao.image.sizes.*.items.resizeMode is deprecated. Please use contao.image.sizes.*.items.resize_mode instead."}}}}},"resizeMode":{"attributes":[],"type":"EnumNode","required":false,"values":["crop","box","proportional"],"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Using contao.image.sizes.*.resizeMode is deprecated. Please use contao.image.sizes.*.resize_mode instead."}},"cssClass":{"attributes":[],"type":"ScalarNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Using contao.image.sizes.*.cssClass is deprecated. Please use contao.image.sizes.*.css_class instead."}},"lazyLoading":{"attributes":[],"type":"BooleanNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Using contao.image.sizes.*.lazyLoading is deprecated. Please use contao.image.sizes.*.lazy_loading instead."}},"skipIfDimensionsMatch":{"attributes":[],"type":"BooleanNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Using contao.image.sizes.*.skipIfDimensionsMatch is deprecated. Please use contao.image.sizes.*.skip_if_dimensions_match instead."}}}}},"target_dir":{"attributes":{"info":"The target directory for the cached images processed by Contao.","example":"%kernel.project_dir%\/assets\/images"},"type":"ScalarNode","required":false,"default":"\/Users\/martin\/Sites\/contao-4\/assets\/images"},"target_path":{"attributes":[],"type":"ScalarNode","required":false,"deprecation":{"package":"contao\/core-bundle","version":"4.9","message":"Use the \"contao.image.target_dir\" parameter instead."},"default":null},"valid_extensions":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":["jpg","jpeg","gif","png","tif","tiff","bmp","svg","svgz","webp"],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}}}},"security":{"attributes":[],"type":"ArrayNode","required":false,"default":{"two_factor":{"enforce_backend":false}},"fields":{"two_factor":{"attributes":[],"type":"ArrayNode","required":false,"default":{"enforce_backend":false},"fields":{"enforce_backend":{"attributes":[],"type":"BooleanNode","required":false,"default":false}}}}},"search":{"attributes":[],"type":"ArrayNode","required":false,"default":{"default_indexer":{"enable":true},"index_protected":false,"listener":{"index":true,"delete":true}},"fields":{"default_indexer":{"attributes":{"info":"The default search indexer, which indexes pages in the database."},"type":"ArrayNode","required":false,"default":{"enable":true},"fields":{"enable":{"attributes":[],"type":"ScalarNode","required":false,"default":true}}},"index_protected":{"attributes":{"info":"Enables indexing of protected pages."},"type":"ScalarNode","required":false,"default":false},"listener":{"attributes":{"info":"The search index listener can index valid and delete invalid responses upon every request. You may limit it to one of the features or disable it completely."},"type":"ArrayNode","required":false,"default":{"index":true,"delete":true},"fields":{"index":{"attributes":{"info":"Enables indexing successful responses."},"type":"ScalarNode","required":false,"default":true},"delete":{"attributes":{"info":"Enables deleting unsuccessful responses from the index."},"type":"ScalarNode","required":false,"default":true}}}}},"crawl":{"attributes":[],"type":"ArrayNode","required":false,"default":{"additional_uris":[],"default_http_client_options":[]},"fields":{"additional_uris":{"attributes":{"info":"Additional URIs to crawl. By default, only the ones defined in the root pages are crawled."},"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}},"default_http_client_options":{"attributes":{"info":"Allows to configure the default HttpClient options (useful for proxy settings, SSL certificate validation and more)."},"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}}}},"mailer":{"attributes":[],"type":"ArrayNode","required":false,"default":{"transports":[]},"fields":{"transports":{"attributes":{"info":"Specifies the mailer transports available for selection within Contao."},"type":"PrototypedArrayNode","required":false,"default":[],"key":"name","prototype":{"attributes":[],"type":"ArrayNode","required":false,"fields":{"from":{"attributes":{"info":"Overrides the \"From\" address for any e-mails sent with this mailer transport."},"type":"ScalarNode","required":false,"default":null}}}}}},"backend":{"attributes":[],"type":"ArrayNode","required":false,"default":{"attributes":[],"custom_css":[],"custom_js":[],"badge_title":""},"fields":{"attributes":{"attributes":{"info":"Adds HTML attributes to the <body> tag in the back end.","example":{"app-name":"My App","app-version":"1.2.3"}},"type":"PrototypedArrayNode","required":false,"default":[],"key":"name","prototype":{"attributes":[],"type":"ScalarNode","required":false}},"custom_css":{"attributes":{"info":"Adds custom style sheets to the back end.","example":["files\/backend\/custom.css"]},"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}},"custom_js":{"attributes":{"info":"Adds custom JavaScript files to the back end.","example":["files\/backend\/custom.js"]},"type":"PrototypedArrayNode","required":false,"default":[],"key":null,"prototype":{"attributes":[],"type":"ScalarNode","required":false}},"badge_title":{"attributes":{"info":"Configures the title of the badge in the back end.","example":"develop"},"type":"ScalarNode","required":false,"default":""}}}}},"nelmio_security":{"bundleName":"NelmioSecurityBundle","bundleAlias":"nelmio_security","attributes":[],"type":"ArrayNode","required":false,"fields":{"clickjacking":{"attributes":[],"type":"ArrayNode","required":false,"fields":{"paths":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":{"^\/.*":{"header":"DENY"}},"key":"pattern","prototype":{"attributes":[],"type":"ArrayNode","required":false,"fields":{"header":{"attributes":[],"type":"ScalarNode","required":false,"default":"DENY"}}}}}}}},"framework":{"bundleName":"FrameworkBundle","bundleAlias":"framework","attributes":[],"type":"ArrayNode","required":false,"fields":{"mailer":{"attributes":{"info":"Mailer configuration"},"type":"ArrayNode","required":false,"default":{"enabled":true,"message_bus":null,"dsn":null,"transports":[],"headers":[]},"fields":{"transports":{"attributes":[],"type":"PrototypedArrayNode","required":false,"default":[],"key":"name","prototype":{"attributes":[],"type":"ScalarNode","required":false}}}}}}}'];

    if (0 !== $exitCode) {
        throw new \RuntimeException('Unable to load editor config: '.$exitCode.': '.implode("\n", $output));
    }

    return json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
}

function loadCurrentValues(): array
{
    $data = (new Parser)->parseFile(PROJECT_DIR.'/config/managed_config.yml', Yaml::PARSE_CUSTOM_TAGS | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

function renderEditor($config, $values)
{
    echo '<!doctype html>
        <title>Config Editor</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
        <body>
        <div class="container" style="max-width: 720px;">
        <form action="" method="POST">
    ';

    foreach ($config as $bundle => $bundleConfig) {
        renderBundle($bundle, $bundleConfig, $values[$bundle] ?? []);
    }

    echo '<button class="my-4 btn btn-primary btn-lg btn-block">Save</button>';
    echo '</form>';
}

function renderBundle($rootKey, $config, array $values)
{
    echo '<div class="card mt-4">
        <div class="card-header">
            '.$config['bundleName'].' ('.$config['bundleAlias'].')
        </div>
        <div class="card-body">
    ';

    renderFields($config['fields'], $rootKey, $values);

    echo '</div></div>';
}

function renderFields($fields, $keyPrefix, array $values)
{
    foreach ($fields as $name => $config) {
        renderField($keyPrefix.'['.$name.']', $config, $values[$name] ?? null);
    }
}

function renderField($name, $config, $value)
{
    if ($value instanceof TaggedValue) {
        $value = (new Dumper)->dump($value, 0, 0, Yaml::DUMP_NULL_AS_TILDE);
    }

    echo '<div class="form-group">';

    $renderFunc = 'render'.$config['type'];

    if (!function_exists($renderFunc)) {
        $renderFunc = 'renderScalarNode';
    }

    if (!empty($config['deprecation'])) {
        echo '<div style="opacity: 0.7;" class="card mt-4"><div class="card-body">';
        echo '<p class="form-text">'.($config['deprecation']['message'] ?? '').'</p>';
    }

    $renderFunc($name, $config, $value);

    if (!empty($config['deprecation'])) {
        echo '</div></div>';
    }

    echo '</div>';
}

function renderScalarNode($name, $config, $value)
{
    echo '<label>'.getLabelFromName($name).'</label>';
    echo '<input name="'.$name.'" type="text" class="form-control" value="'.htmlspecialchars((string) ($value ?? '')).'" placeholder="'.htmlspecialchars(trim(json_encode($config['default'] ?? ''), '"')).'">';
    echo '<small class="form-text text-muted">'.htmlspecialchars($config['attributes']['info'] ?? '').'</small>';
}

function renderVariableNode($name, $config, $value)
{
    if ($value !== null) {
        $value = (new Dumper)->dump($value, 1, 0, Yaml::DUMP_NULL_AS_TILDE);
    }
    if ($config['default'] ?? null !== null) {
        $config['default'] = (new Dumper)->dump($config['default'], 1, 0, Yaml::DUMP_NULL_AS_TILDE);
    }
    echo '<label>'.getLabelFromName($name).'</label>';
    echo '<textarea name="'.$name.'" type="text" class="form-control" placeholder="'.htmlspecialchars(trim(json_encode($config['default'] ?? ''), '"')).'">'.htmlspecialchars((string) ($value ?? '')).'</textarea>';
    echo '<small class="form-text text-muted">'.htmlspecialchars($config['attributes']['info'] ?? '').'</small>';
}

function renderEnumNode($name, $config, $value)
{
    echo '<label>'.getLabelFromName($name).'</label>';
    echo '<select name="'.$name.'" class="form-control">';
    echo '<option value="">-</option>';
    foreach ($config['values'] ?? [] as $option) {
        echo '<option value="'.htmlspecialchars($option).'">'.htmlspecialchars($option).'</option>';
    }
    echo '</select>';
    echo '<small class="form-text text-muted">'.htmlspecialchars($config['attributes']['info'] ?? '').'</small>';
}

function renderBooleanNode($name, $config, $value)
{
    $inverted = !empty($config['default']);

    if ($value === false && $inverted) {
        $value = true;
    }

    echo '<div class="form-check">
        <label class="form-check-label">
            <input name="'.$name.'" class="form-check-input" type="checkbox" value="'.($inverted ? '0' : '1').'"'.($value ? ' checked' : '').'>
            '.($inverted ? 'Disable ' : '').getLabelFromName($name).'
        </label>
    </div>';
    echo '<small class="form-text text-muted">'.htmlspecialchars($config['attributes']['info'] ?? '').'</small>';
}

function renderPrototypedArrayNode($name, $config, $value) {
    echo '<div class="card mt-4">
        <div class="card-header">
            '.getLabelFromName($name).'
        </div>
        <div class="card-body">
    ';
    echo '<p class="form-text">'.htmlspecialchars($config['attributes']['info'] ?? '').'</p>';

    for ($i = 0; $i <= \count($value ?? []); $i++) {
        $childName = $name.'['.$i.']';
        if (!empty($config['key'])) {
            renderField($childName.'['.$config['key'].']', [
                'type' => 'ScalarNode',
                'required' => true,
            ], array_keys($value ?? [])[$i] ?? null);
            if ($config['prototype']['type'] !== 'ArrayNode') {
                $childName .= '[value]';
            }
        }

        renderField($childName, $config['prototype'], array_values($value ?? [])[$i] ?? null);
    }

    echo '</div></div>';
}

function renderArrayNode($name, $config, $value) {
    echo '<p class="form-text">'.htmlspecialchars($config['attributes']['info'] ?? '').'</p>';
    renderFields($config['fields'], $name, $value ?? []);
}

function getLabelFromName($name) {
    $parts = array_map('ucfirst', explode('][', str_replace('_', ' ', trim(explode('[', $name, 2)[1], '[]'))));

    $number = implode('.', array_map(function ($num) { return (int)$num + 1; }, array_filter($parts, 'is_numeric'))).'. ';
    $parts = array_filter($parts, function ($part) { return !is_numeric($part); });

    if (end($parts) === 'Value') {
        array_pop($parts);
    }

    return ltrim($number.implode(' ', $parts), ' .');
}

function saveYaml($data, $config)
{
    $data = filterEmptyData($data);
    $data = processFields($data, $config);
    $yaml = (new Dumper)->dump($data, 999, 0, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_NULL_AS_TILDE | Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE);

    if ($data === []) {
        $yaml = '';
    }

    #echo '<pre>';
    #echo htmlspecialchars($yaml);
    #echo '</pre>';

    file_put_contents(PROJECT_DIR.'/config/managed_config.yml', $yaml);

    return $data;
}

function filterEmptyData(array $data): array
{
    foreach (array_keys($data) as $key) {
        if (is_array($data[$key])) {
            $data[$key] = filterEmptyData($data[$key]);
        }
        if ($data[$key] === '' || $data[$key] === []) {
            unset($data[$key]);
        }
    }

    return $data;
}

function processFields(array $data, array $fields)
{
    foreach (array_keys($data) as $key) {
        if (isset($fields[$key])) {
            $data[$key] = processNode($data[$key], $fields[$key]);
        }
        else {
            unset($data[$key]);
        }
    }

    return $data;
}

function processPrototypeArray(array $data, array $config)
{
    $data = array_values($data);

    if (!empty($config['key'])) {
        $dataByKey = [];
        foreach (array_keys($data) as $key) {
            if (!empty($data[$key][$config['key']])) {
                $keyValue = $data[$key][$config['key']];
                unset($data[$key][$config['key']]);
                if (empty($config['prototype']['fields']) && array_keys($data[$key]) === ['value']) {
                    $data[$key] = $data[$key]['value'];
                }
                $dataByKey[$keyValue] = $data[$key];
            }
        }
        $data = $dataByKey;
    }

    foreach (array_keys($data) as $key) {
        $data[$key] = processNode($data[$key], $config['prototype']);
    }

    return $data;
}

function processNode($data, array $config)
{
    if (is_array($data) && !empty($config['fields'])) {
        return processFields($data, $config['fields']);
    }

    if (is_array($data) && !empty($config['prototype'])) {
        return processPrototypeArray($data, $config);
    }

    if ($config['type'] === 'VariableNode' && is_string($data)) {
        $data = parseYamlFallbackString($data);
    }

    if ($config['type'] === 'IntegerNode') {
        $data = (int) $data;
    }

    if ($config['type'] === 'NumericNode') {
        $data = (float) $data;
    }

    if ($config['type'] === 'BooleanNode') {
        $data = (bool) $data;
    }

    if ($config['type'] === 'EnumNode') {
        // use value from the values array
    }

    if ($config['type'] === 'ScalarNode') {
        if (is_string($data)) {
            $parsedData = parseYamlFallbackString($data);
            if ($parsedData instanceof TaggedValue || (!is_array($parsedData) && !is_object($parsedData))) {
                $data = $parsedData;
            }
        }
        else {
            $data = json_encode($data);
        }
    }

    return $data;
}

function parseYamlFallbackString(string $data)
{
    try {
        return (new Parser)->parse($data, Yaml::PARSE_CUSTOM_TAGS | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }
    catch (\Throwable $e) {
        return $data;
    }
}
