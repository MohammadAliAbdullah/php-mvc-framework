<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace App\Core\System\Component;


use function App\Core\System\utils\app;
use function App\Core\System\utils\array2xml;
use function App\Core\System\Utils\cssToXpath;
use function App\Core\System\utils\dashesToCamelCase;
use function App\Core\System\utils\prepareJson;
use function App\Core\System\utils\removeJsonComments;

use App\Core\View\View;
use App\Core\System\Cache;
use App\Core\Http\Response;
use App\Core\System\ClassReflector;
use App\Core\Exceptions\NotFoundHttpException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;

if (! defined('COMPONENT_CACHE_FLAG_LOCK')) {
    define('COMPONENT_CACHE_FLAG_LOCK', PHP_INT_MIN + 1);
    define('COMPONENT_CACHE_FLAG_REGENERATE', PHP_INT_MIN + 2);
    //time
    define('COMPONENT_CACHE_EXPIRE_DELAY', 5); //real expiration time +5 seconds
    define('COMPONENT_CACHE_WAIT', 1); //wait for cache generation
    define('COMPONENT_CACHE_MAX_WAIT_RETRY', 3); //wait for cache generation
    define('COMPONENT_CACHE_LOCK_EXPIRE', 20); //lock can not be set more than COMPONENT_CACHE_LOCK_EXPIRE seconds
    define('COMPONENT_CACHE_EXPIRE', 20);
}

class ComponentBackup {
    static private $instance;

    private $queue;
    private mixed $htmlTemplate = null;
    /**
     * @var ComponentRecord[]
     */
    private array $components = [];
    private $componentsFile = null;

    private $loaded = false;

    private $content = false;

    private $view = null;

    private $documentType = 'html';

    private $cache = [];

    private $cached = false;

    private $regenerate = false;

    static function getInstance($view = false, $regenerate = false, $content = false) {
        if (self::$instance === NULL) {
            if (! $view) {
                $view = app()->make(View::class);
            }
            self :: $instance =  new self($view, $regenerate, $content);
        }

        return self::$instance;
    }

    function __construct($view, $regenerate = false, $content = false) {
        if ($this->loaded) {
            return true;
        }

        if (! $view) {
            $view = app()->make(View::class);
        }

        $this->view         = $view;

        $this->componentsFile = $view->serviceTemplate() . '.component';
        $this->content        = $content;
        $this->htmlTemplate = $view->getHtmlTemplate();
        $this->documentType = $view->getDocumentType();

        if ((! file_exists($this->componentsFile)) || $regenerate) {
            $this->generateRequiredComponents();
            $this->cacheComponentsData();
            $this->saveTemplateComponents();
            $this->loadComponents();
            $this->loaded = true;
        }
        if ($this->loaded) {
            return true;
        }

        $this->loadTemplateComponents();
        if($this->regenerate){
            $this->generateRequiredComponents();
            $this->cacheComponentsData();
            $this->saveTemplateComponents();
        }
        if(!$this->loaded){
            $this->loadComponents();
        }
        $this->loaded = true;
    }

    function isComponent($name) {
        return isset($this->components[$name]);
    }

    function getComponent($name) {
        return $this->components[$name] ?? [];
    }

    private function loadComponents(): void {
        $cacheDriver = Cache::getInstance();
        $namespace = 'component';  // Default namespace
        $notFound404 = false;
        $retry = 0;
        $wait = true;

        while ($wait && $retry < COMPONENT_CACHE_MAX_WAIT_RETRY) {
            $wait = false;
            $retry++;

            foreach ($this->components as $componentName => $componentRecord) {
                if (!$componentRecord instanceof ComponentRecord) {
                    error_log("Invalid component record for (loadComponents()): $componentName");
                    continue;
                }

                $class = $componentRecord->get('class');
                $file = $componentRecord->get('filePath');
                $templateFile = $this->view->serviceTemplate();
                $namespace = $componentRecord->get('namespace') ?? 'component';
                $cacheKey = $componentRecord->get('cacheKey') ?? null;
                $cacheExpireKey = $cacheKey . '_expire';

                if (!$class || !file_exists($file)) {
                    error_log("Component class/file missing: $componentName - $class - $file");
                    continue;
                }

                $results = $cacheDriver->get($namespace, $cacheKey);;
                $cacheExpire = $cacheDriver->get($namespace, $cacheExpireKey);
                if ($cacheExpire !== null && $cacheExpire < time()) {
                    $cacheDriver->delete($namespace, $cacheKey);
                }

                if ($cacheExpire == COMPONENT_CACHE_FLAG_LOCK) {
                    $wait = true;
                } elseif (
                    !$results
                    || file_exists($templateFile) && ((file_exists($file) ? filemtime($file) : 0) > (file_exists($templateFile) ? filemtime($templateFile) : 0))
                    || ($cacheExpire == COMPONENT_CACHE_FLAG_REGENERATE || $cacheExpire === null)) {
                    try {
                        //resolve dependencies
                        $componentInstance = Container::getInstance()->make($class, ['options' => $componentRecord->get('options')]);
                        $results = $componentInstance->results($this->view->parameters);
                    } catch (\Throwable $e) {
                        error_log("Error instantiating component: $componentName - " . $e->getMessage());
                        continue;
                    }
                }
                if ($results !== null) {
//                    $cacheDriver->set($namespace, $cacheKey, $results, time() + COMPONENT_CACHE_EXPIRE);
//                    $cacheDriver->set($namespace, $cacheExpireKey, time() + COMPONENT_CACHE_EXPIRE);
                    $this->view->_component[$componentName] = $results;
                }
            }

            if ($wait) {
                error_log('Waiting for cache regeneration ' . $_SERVER['REQUEST_URI']);
                sleep(COMPONENT_CACHE_WAIT);
            }
        }

        if ($retry >= COMPONENT_CACHE_MAX_WAIT_RETRY) {
            error_log('Error: CACHE max retry reached for ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        }

        $this->components = []; // Ensure all components are processed before clearing

        if ($notFound404) {
            $this->handleNotFound();
        }
    }

    /**
     * Caches component data to optimize retrieval and performance.
     */
    private function cacheComponentsData(): void {
        $this->cache = [];
        $cacheDriver = Cache::getInstance();

        foreach ($this->components as $componentName => $componentRecord) {
            if (!$componentRecord instanceof ComponentRecord) {
                error_log("Invalid component record for (cacheComponentsData()): $componentName");
                continue;
            }

            $cacheKey = $componentRecord->get('cacheKey');
            $cacheExpire = $componentRecord->get('cacheExpire');
            $namespace = $componentRecord->get('namespace');
            $class = $componentRecord->get('class');
            $cacheExpireKey = $cacheKey . '_expire';

            if (!$class || !class_exists($class) || !method_exists($class, 'results')) {
                error_log("Invalid class or missing results method (cacheComponentsData()): $class for $componentName");
                continue;
            }

            // Lock cache to prevent simultaneous writes
            $cacheDriver->set($namespace, $cacheExpireKey, COMPONENT_CACHE_FLAG_LOCK, COMPONENT_CACHE_LOCK_EXPIRE);

            // Retrieve component data and store it in cache
            try {
                if(isset($this->_component[$componentName])){
                    $results = $this->_component[$componentName];
                }else{
                    //resolve dependencies
                    $componentInstance = Container::getInstance()->make($class, ['options' => $componentRecord->get('options')]);
                    // $componentInstance = new $class($componentRecord->get('options'));
                    $results = $componentInstance->results($this->view->parameters);
                }
                
            } catch (\Throwable $e) {
                error_log("Error processing component (cacheComponentsData()): $componentName - " . $e->getMessage());
                continue;
            }

            if ($results !== null) {
                $cacheDriver->set($namespace, $cacheKey, $results, $cacheExpire);
                $cacheDriver->set($namespace, $cacheExpireKey, time() + COMPONENT_CACHE_EXPIRE);
                $this->cache[$componentName] = $componentRecord->toArray();
            }
        }

        $this->cached = true;
    }


    /**
     * @throws \Exception
     */
    function saveTemplateComponents(): bool|int
    {
        if (file_exists($this->componentsFile)) {
            include_once $this->componentsFile;
            if (isset($components) && count($components)) {
                foreach($components as $componentName => $componentRecord) {
                    if(
                        isset($this->cache[$componentName]['namespace'])
                        && isset($this->cache[$componentName]['cacheKey'])
                        && $this->cache[$componentName]['namespace'] == $componentRecord['namespace']
                        && $this->cache[$componentName]['cacheKeyExpire'] > $componentRecord['cacheKeyExpire']
                    ) {
                        $cacheDriver = Cache::getInstance();
                        $cacheDriver->delete($componentRecord['namespace'], $componentRecord['cacheKey']);
                    }
                }
            }
        }


        try {
            $php = var_export($this->cache, true);
            $php = preg_replace('/\s+/', ' ', $php);
            //repeating end lines
            $php = preg_replace('/\n+/', '', $php);

            $r = file_put_contents($this->componentsFile, '<?php $components=' . $php . ';');
            if ($r === false) {
                throw new \Exception("Failed to save components to file (saveTemplateComponents()).");
            }
            $this->cache = $r;

            return true;
        }catch (\Throwable $e) {
            error_log("Error saving template components: " . $e->getMessage());
            return false;
        }
    }

    function loadTemplateComponents(): bool
    {
        if (!file_exists($this->componentsFile)) {
            error_log("Components file not found: " . $this->componentsFile);
            return false;
        }
        include_once $this->componentsFile;
        if (!isset($components)) {
            error_log("Components array not defined in file: " . $this->componentsFile);
            return false;
        }

        $this->cache = $components;
        $this->cached = true;
        $templateFile = $this->view->serviceTemplate();
        $cacheDriver = Cache::getInstance();
        foreach($components as $componentName => $component) {
            if (!isset($component['filePath']) || !file_exists($component['filePath'])) {
                continue;
            }

            $componentRecord = $this->createComponentRecord($component['filePath']);
            if (!$componentRecord) {
                error_log("Failed to create component record for: " . $component['filePath']);
                continue;
            }
            $componentRecord->set('_cacheKey', $component['cacheKey']);
            if(isset($component['options'])){
                $componentRecord->set('_options', $component['options']);
            }
            $this->components[$componentName] = $componentRecord;
            $file = $componentRecord->get('filePath');
            if(file_exists($templateFile) && file_exists($file)) {
                if (filemtime($file) > filemtime($templateFile)) {
                    $this->regenerate = true;
                    $cacheDriver->delete($componentRecord->get('namespace'), $componentRecord->get('cacheKey'));
                }
            }
        }

        //keep only requested service
        if (isset($_GET['component_ajax'])) {}

        return true;
    }

    function generateRequiredComponents(): void
    {
        $document = $this->initializeDocument();
        $view = $this->view;

        libxml_use_internal_errors(true);
        if (!empty($errors)) {
            foreach ($errors as $error) {
                error_log("XML Error: " . $error->message);
            }
            libxml_clear_errors();
        }

        $this->loadDocumentContent($document, $view);
        $xpath = new \DOMXpath($document);

        // Include external fragments if required
        $this->includeExternalComponents($document, $xpath, $view);

        $cacheDriver = Cache::getInstance();
        foreach ($this->components as $componentName => $componentRecord) {
            $cacheDriver->delete($componentRecord->get('namespace'), $componentRecord->get('cacheKey'));
        }
        // Process component elements and set $this->components records
        $this->processComponents($document, $xpath);
    }


    private function initializeDocument(): \DomDocument
    {
        $document = new \DomDocument();
        $document->preserveWhiteSpace = false;
        $document->recover = true;
        $document->strictErrorChecking = false;
        $document->formatOutput = false;
        $document->resolveExternals = false;
        $document->validateOnParse = false;
        $document->xmlStandalone = true;

        return $document;
    }

    private function loadDocumentContent($document, $view): void
    {
        if ($this->content) {
            if ($this->documentType == 'html') {
                @$document->loadHTML($this->content);
            } else {
                @$document->loadXML($this->content);
            }
        } else {
            $this->loadTemplateFile($document, $view);
        }
    }

    private function loadTemplateFile($document, $view): void
    {
        if (!file_exists($this->htmlTemplate)) {
            error_log("Template file not found: " . $this->htmlTemplate);
            return;
        }
        if ($this->documentType == 'html') {
            @$document->loadHTMLFile($this->htmlTemplate, LIBXML_NOWARNING | LIBXML_NOERROR);
        } else {
            $content = file_get_contents($this->htmlTemplate);

            if ($this->documentType == 'json') {
                $content = removeJsonComments($content);
                $json = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decoding error: " . json_last_error_msg());
                    return;
                }
                $json = prepareJson($json);
                $xml = array2xml($json);
                @$document->loadXML($xml);
            } else {
                @$document->loadXML($content, LIBXML_NOWARNING | LIBXML_NOERROR);
            }
        }
    }

    private function includeExternalComponents($document, $xpath, $view): void
    {
        $i = 0;

        while (($elements = $xpath->query('//*[@data-v-copy-from or @data-v-save-global]')) && $elements->length && $i++ < 2) {
            $fromDocument = $this->initializeDocument();

            foreach ($elements as $element) {
                $attribute = $element->getAttribute('data-v-copy-from') ?: $element->getAttribute('data-v-save-global');
                $element->removeAttribute('data-v-copy-from');
                $element->removeAttribute('data-v-save-global');

                if (preg_match('/([^\,]+)\,([^$,]+)/', $attribute, $from)) {
                    $file = html_entity_decode(trim($from[1]));
                    $selector = html_entity_decode(trim($from[2]));

                    $filePath = $view->getTemplatePath() . $file;
                    if (!file_exists($filePath)) {
                        error_log("External component file not found: " . $filePath);
                        continue;
                    }

                    if ($this->documentType == 'html') {
                        @$fromDocument->loadHTMLFile($filePath);
                    } else {
                        @$fromDocument->loadXML($filePath);
                    }

                    $fromXpath = new \DOMXpath($fromDocument);
                    $fromElements = $fromXpath->query(cssToXpath($selector));

                    $this->importElements($document, $element, $fromElements);
                }
            }
        }
    }
    private function importElements($document, $element, $fromElements): void
    {
        if (!$fromElements || $fromElements->length === 0) {
            error_log("Warning: No elements found to import.");
            return;
        }
        $count = 0;
        $parent = $element->parentNode;

        if (!$parent) {
            error_log("Error: Element parent not found.");
            return;
        }

        foreach ($fromElements as $externalNode) {
            $importedNode = $document->importNode($externalNode, true);

            if (!$importedNode) {
                error_log("Error: Failed to import node.");
                continue;
            }

            if ($count) {
                $parent->appendChild($importedNode);
            } else {
                $parent->replaceChild($importedNode, $element);
            }

            $element = $importedNode;
            $count++;
        }
    }

    private function processComponents($document, $xpath): void
    {
        $elements = $xpath->query('//*[@*[starts-with(name(), "data-v-component-")]]');
        $components = [];
        $optionsMap = [];
        foreach ($elements as $element) {
            $componentName = '';
            // Extract component name and options from attributes
            foreach ($element->attributes as $attr) {
                $attrName = $attr->nodeName;
                $attrValue = $attr->nodeValue;

                // Match component definitions: data-v-component-*
                if (str_starts_with($attrName, 'data-v-component-')) {
                    $componentName = substr($attrName, strlen('data-v-component-'));
                    if (!empty($componentName)) {
                        $components[$componentName] = true;
                    }
                }
                // Match component options: data-v-opt-*
                if (str_starts_with($attrName, 'data-v-opt-')) {
                    $optionComponent = substr($attrName, strlen('data-v-opt-'));

                    // Decode JSON-like attributes to an associative array
                    $parsedOptions = json_decode($attrValue, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $attrValue = preg_replace([
                            '/([{,])\s*([a-zA-Z0-9_]+)\s*:/',  // Convert missing-quoted keys: {site_id: -> {"site_id":
                            '/([{,])\s*\'([a-zA-Z0-9_]+)\'\s*:/', // Convert single-quoted keys: {'site_id': -> {"site_id":
                            '/:\s*\'(.*?)\'(\s*[},\]])/',        // Convert single-quoted values: 'mango' -> "mango"
                            '/:\s*([a-zA-Z_][a-zA-Z0-9_ ]*)(\s*[},\]])/'  // Convert unquoted string values (multi-word): site_id: sydney -> "sydney"
                        ], [
                            '$1"$2":',
                            '$1"$2":',
                            ': "$1"$2',
                            ': "$1"$2'
                        ], $attrValue);
                    }
                    $parsedOptions = json_decode($attrValue, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("JSON decoding error in options: " . json_last_error_msg());
                        continue;
                    }
                    $optionsMap[$optionComponent] = $parsedOptions;
                }
            }
        }
        $cacheDriver = Cache::getInstance();
        // Process each component and apply extracted options
        foreach (array_keys($components) as $component) {
            $options = $optionsMap[$component] ?? [];
            $this->extractComponentData($component, $options, $cacheDriver);
        }
    }

    private function extractComponentData(string $componentName, array $opts, Cache $cacheDriver): void {
        // Check if the component is already processed
        if (isset($this->components[$componentName])) {
            $componentRecord = $this->components[$componentName];
            $existingOptions = $componentRecord->get('options');
            $validOptions = $componentRecord->get('validOptions');
        } else {
            //Resolve Component Class
            $componentRecord = $this->resolveComponentRecord($componentName);
            if (!$componentRecord || $componentRecord->get('designOnly')) {
                error_log("Component record not found (extractComponentData()): $componentName");
                return;
            }

            // Validate component options if not previously processed
            $validOptions = $componentRecord->get('validOptions');
            $existingOptions = [];
        }
        if (!is_array($validOptions)) {
            error_log("Invalid validOptions format for: $componentName");
            $validOptions = [];
        }

        // Identify updated options (new keys or different values)
        $updatedOptions = $this->validateOptions($opts, $validOptions, $existingOptions);


        // Merge updated options with existing ones
        $newOptions = array_merge($existingOptions, $updatedOptions);

        // Generate hash for the updated component
        $newOptions['_hash'] = md5($componentName . serialize($newOptions));

        // Update existing ComponentRecord or create a new one if needed
        $componentRecord->set('_options', $newOptions);

        $hash = $componentRecord->getHash();
        // Determine namespace format based on whether it's a plugin or core component

        $namespace = str_starts_with($componentName, 'plugin-') ? "component.plugin.{$componentName}" : "component";
        $cacheKey = "{$componentName}." . SITE_ID . ".{$hash}";
        $componentRecord->set('_namespace', $namespace);
        $componentRecord->set('_cacheKey', $cacheKey);
        $componentRecord->set('_cacheExpire',time() + COMPONENT_CACHE_EXPIRE);
        $this->components[$componentName] = $componentRecord;
        $cacheDriver->delete($namespace, $cacheKey);
    }

    private function resolveComponentRecord($component): ?ComponentRecord
    {
        $plugin = str_starts_with($component, 'plugin');
        $component = str_replace('plugin-', '', $component);
        // Extract plugin name and namespace
        $pluginName = $plugin ? strtok($component, '-') : null;
        if ($plugin && empty($pluginName)) {
            error_log("Invalid plugin component name: $component");
            return null;
        }

        $nameSpace = $plugin ? substr($component, strlen($pluginName) + 1) : $component;

        // Define possible template paths
        $possibleTemplatePaths = [
            $plugin ? DIR_PLUGINS . "$pluginName/Components/" . str_replace('-', '/', $nameSpace) . '.php' : null,
            DIR_CORE . 'Components/' . str_replace('-', '/', $component) . '.php',
        ];
        // Find the first valid component file
        foreach ($possibleTemplatePaths as $file) {
            if ($file && file_exists($file)) {
                $record = $this->createComponentRecord($file);
                if (!$record) {
                    error_log("Failed to create component record for: $file");
                }
                return $record;
            }
        }
        return null; // Return null if no valid class is found
    }

    public function createComponentRecord($file): ?ComponentRecord
    {
        if (!file_exists($file)) {
            error_log("Component file does not exist: $file");
            return null;
        }

        $reflection = ClassReflector::createFromFile($file);
        if (!$reflection) {
            error_log("Failed to reflect class from file: $file");
            return null;
        }

        $componentClass = $reflection->getName();
        if (!method_exists($componentClass, 'getComponentMeta')) {
            error_log("getComponentMeta() missing in class: $componentClass");
            return null;
        }

        $meta = $componentClass::getComponentMeta();
        if (!$meta || !is_array($meta)) {
            error_log("Invalid metadata for component class: $componentClass");
            return null;
        }

        $meta['file'] = $file;
        $meta['class'] = $componentClass;

        return new ComponentRecord($meta);
    }


    public function validateOptions($opts, $validOptions, $existingOptions): array
    {
        $updatedOptions = [];

        foreach ($opts as $name => $value) {
            // Ensure the option name exists in valid options
            if (!in_array($name, $validOptions)) {
                continue;
            }

            // Ensure value is not null
            if ($value === null || $value === '') {
                continue;
            }

            // Check if value has changed or is a new key
            if (!array_key_exists($name, $existingOptions) || $existingOptions[$name] !== $value) {
                $updatedOptions[$name] = $value;
            }
        }

        return $updatedOptions;
    }






    /**
     * Invokes the request method for each component, if applicable.
     */
    private function invokeComponentRequests(): void {
        foreach ($this->components as $componentName => $componentRecord) {
            if (!$componentRecord instanceof ComponentRecord) {
                continue;
            }

            foreach ($componentRecord->get('options', []) as $index => $options) {
                if (isset($this->view->_component[$componentName])) {
                    $comp = &$this->view->_component[$componentName];
                    $results = &$comp[$index];
                    $object = $results['_instance'] ?? false;

                    if ($object && method_exists($object, 'request')) {
                        $object->request($results, $index);
                    }
                }
            }
        }
    }

    /**
     * Handles 404 errors by generating a response.
     */
    private function handleNotFound(): void {
        try {
            Response::fromException(app()->make(NotFoundHttpException::class));
        } catch (BindingResolutionException $e) {
            Response::fromException($e);
        }
    }

}
