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

class Component {
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

    // Modern DOM configuration constants
    private const DOM_LOAD_OPTIONS = LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOCDATA | LIBXML_NOENT;
    private const DOM_HTML_LOAD_OPTIONS = LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR;
    private const DOM_HTML_FILE_OPTIONS = LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR; // For createFromFile

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
            $this->cacheComponentsData(true);
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
            $this->cacheComponentsData(true);
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

                $results = $cacheDriver->get($namespace, $cacheKey);
                $cacheExpire = $cacheDriver->get($namespace, $cacheExpireKey);
                
                // Check if cache is expired and delete it
                //Need to check $cacheExpire variable was set with a 100 seconds
                if ($cacheExpire !== null && $cacheExpire < time() && $cacheExpire != COMPONENT_CACHE_FLAG_LOCK) {
                    $cacheDriver->delete($namespace, $cacheKey);
                    $cacheDriver->delete($namespace, $cacheExpireKey);
                    $cacheExpire = null;
                }else if(!$results){
                    $cacheExpire = null;
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
                        
                        // Cache the results if we got them
                        if ($results !== null) {
                            $cacheDriver->set($namespace, $cacheKey, $results, time() + COMPONENT_CACHE_EXPIRE);
                            $cacheDriver->set($namespace, $cacheExpireKey, time() + COMPONENT_CACHE_EXPIRE);
                        }
                    } catch (\Throwable $e) {
                        error_log("Error instantiating component: $componentName - " . $e->getMessage());
                        continue;
                    }
                }
                if ($results !== null) {
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
    private function cacheComponentsData($forceExpire = false): void {
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

            // Check if cache is already locked
            if($forceExpire){
                $cacheDriver->delete($namespace, $cacheExpireKey);
            }
            $currentCacheExpire = $cacheDriver->get($namespace, $cacheExpireKey);
            if ($currentCacheExpire == COMPONENT_CACHE_FLAG_LOCK) {
                continue; // Skip if already being processed
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
                // Remove lock on error
                $cacheDriver->delete($namespace, $cacheExpireKey);
                continue;
            }

            if ($results !== null) {
                $cacheDriver->set($namespace, $cacheKey, $results, $cacheExpire);
                $cacheDriver->set($namespace, $cacheExpireKey, time() + COMPONENT_CACHE_EXPIRE);
                $this->cache[$componentName] = $componentRecord->toArray();
            } else {
                // Remove lock if no results
                $cacheDriver->delete($namespace, $cacheExpireKey);
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

        // Enable internal error handling for better error management
        libxml_use_internal_errors(true);
        $errors = libxml_get_errors();
        if (!empty($errors)) {
            foreach ($errors as $error) {
                error_log("XML Error: " . $error->message);
            }
            libxml_clear_errors();
        }

        $this->loadDocumentContent($document, $view);
        $xpath = new \Dom\XPath($document);

        // Include external fragments if required
        $this->includeExternalComponents($document, $xpath, $view);

        $cacheDriver = Cache::getInstance();
        foreach ($this->components as $componentName => $componentRecord) {
            $cacheDriver->delete($componentRecord->get('namespace'), $componentRecord->get('cacheKey'));
        }
        // Process component elements and set $this->components records
        $this->processComponents($document, $xpath);
    }


    /**
     * Initialize a modern DOM document with optimized settings
     */
    private function initializeDocument(): \Dom\HTMLDocument|\Dom\XMLDocument
    {
        if ($this->documentType == 'html') {
            $document = \Dom\HTMLDocument::createEmpty('UTF-8');
        } else {
            $document = \Dom\XMLDocument::createEmpty();
        }
        
        // $document->preserveWhiteSpace = false;
        // $document->recover = true;
        // $document->strictErrorChecking = false;
        // $document->formatOutput = false;
        // $document->resolveExternals = false;
        // $document->validateOnParse = false;
        // $document->xmlStandalone = true;
        // $document->substituteEntities = false;
        // $document->encoding = 'UTF-8';

        return $document;
    }

    /**
     * Load document content with improved error handling
     */
    private function loadDocumentContent($document, $view): void
    {
        try {
            if ($this->content) {
                if ($this->documentType == 'html') {
                    $this->loadHtmlContent($document, $this->content);
                } else {
                    $this->loadXmlContent($document, $this->content);
                }
            } else {
                $this->loadTemplateFile($document, $view);
            }
        } catch (\Throwable $e) {
            error_log("Error loading document content: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load HTML content with modern DOM handling
     */
    private function loadHtmlContent(\Dom\HTMLDocument $document, string $content): void
    {
        // Ensure proper HTML structure
        if (!preg_match('/<html/i', $content)) {
            $content = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $content . '</body></html>';
        }
        
        $document->loadHTML('<?xml encoding="UTF-8">' . $content);
        foreach ($document->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $document->removeChild($item); // remove hack
            }
        }

    }

    /**
     * Load XML content with modern DOM handling
     */
    private function loadXmlContent(\Dom\XMLDocument $document, string $content): void
    {
        $document->loadXML($content, self::DOM_LOAD_OPTIONS);
    }

    /**
     * Load template file with improved error handling
     */
    private function loadTemplateFile($document, $view): void
    {
        if (!file_exists($this->htmlTemplate)) {
            error_log("Template file not found: " . $this->htmlTemplate);
            return;
        }

        try {
            if ($this->documentType == 'html') {
                $this->loadHtmlFile($document, $this->htmlTemplate);
            } else {
                $this->loadXmlFile($document, $this->htmlTemplate);
            }
        } catch (\Throwable $e) {
            error_log("Error loading template file: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load HTML file with modern DOM handling
     */
    private function loadHtmlFile(\Dom\HTMLDocument $document, string $filePath): void
    {
        // Modern Dom\HTMLDocument uses createFromFile() instead of loadHTMLFile()
        // We need to create a new document from the file and then merge it
        // Read file content
        $newDocument = \Dom\HTMLDocument::createFromFile($filePath, self::DOM_HTML_FILE_OPTIONS, 'UTF-8');         

        // Import the document element from the new document into the existing one
        if ($newDocument->documentElement) {
            $importedElement = $document->importNode($newDocument->documentElement, true);
            $document->appendChild($importedElement);
        }
    }

    /**
     * Load XML file with modern DOM handling
     */
    private function loadXmlFile(\Dom\XMLDocument $document, string $filePath): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: $filePath");
        }

        if ($this->documentType == 'json') {
            $this->loadJsonContent($document, $content);
        } else {
            $document->loadXML($content, self::DOM_LOAD_OPTIONS);
        }
    }

    /**
     * Load JSON content and convert to XML
     */
    private function loadJsonContent(\Dom\XMLDocument $document, string $content): void
    {
        $content = removeJsonComments($content);
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decoding error: " . json_last_error_msg());
            throw new \RuntimeException("Invalid JSON content: " . json_last_error_msg());
        }
        
        $json = prepareJson($json);
        $xml = array2xml($json);
        $document->loadXML($xml, self::DOM_LOAD_OPTIONS);
    }

    /**
     * Include external components with improved error handling
     */
    private function includeExternalComponents($document, $xpath, $view): void
    {
        $maxIterations = 2;
        $iteration = 0;

        while ($iteration++ < $maxIterations) {
            $elements = $xpath->query('//*[@data-v-copy-from or @data-v-save-global]');
            
            if (!$elements || $elements->length === 0) {
                break;
            }

            $fromDocument = $this->initializeDocument();

            foreach ($elements as $element) {
                $this->processExternalComponent($document, $xpath, $element, $fromDocument, $view);
            }
        }
    }

    /**
     * Process individual external component
     */
    private function processExternalComponent(\Dom\HTMLDocument|\Dom\XMLDocument $document, \Dom\XPath $xpath, \Dom\Element $element, \Dom\HTMLDocument|\Dom\XMLDocument $fromDocument, $view): void
    {
        $attribute = $element->getAttribute('data-v-copy-from') ?: $element->getAttribute('data-v-save-global');
        $element->removeAttribute('data-v-copy-from');
        $element->removeAttribute('data-v-save-global');

        if (!preg_match('/([^\,]+)\,([^$,]+)/', $attribute, $from)) {
            error_log("Invalid external component attribute format: $attribute");
            return;
        }

        $file = html_entity_decode(trim($from[1]));
        $selector = html_entity_decode(trim($from[2]));

        $filePath = $view->getTemplatePath() . $file;
        if (!file_exists($filePath)) {
            error_log("External component file not found: " . $filePath);
            return;
        }

        try {
            $this->loadExternalDocument($fromDocument, $filePath);
            $fromXpath = new \Dom\XPath($fromDocument);
            $fromElements = $fromXpath->query(cssToXpath($selector));
            $this->importElements($document, $element, $fromElements);
        } catch (\Throwable $e) {
            error_log("Error processing external component: " . $e->getMessage());
        }
    }

    /**
     * Load external document with proper error handling
     */
    private function loadExternalDocument(\Dom\HTMLDocument|\Dom\XMLDocument $document, string $filePath): void
    {
        if ($this->documentType == 'html') {
            $document->loadHTMLFile($filePath, self::DOM_HTML_LOAD_OPTIONS);
        } else {
            $document->loadXML($filePath, self::DOM_LOAD_OPTIONS);
        }
    }

    /**
     * Import elements with improved error handling and validation
     */
    private function importElements(\Dom\HTMLDocument|\Dom\XMLDocument $document, \Dom\Element $element, \Dom\NodeList $fromElements): void
    {
        if (!$fromElements || $fromElements->length === 0) {
            error_log("Warning: No elements found to import.");
            return;
        }

        $parent = $element->parentNode;
        if (!$parent) {
            error_log("Error: Element parent not found.");
            return;
        }

        $count = 0;
        foreach ($fromElements as $externalNode) {
            try {
                $importedNode = $document->importNode($externalNode, true);
                if (!$importedNode) {
                    error_log("Error: Failed to import node.");
                    continue;
                }

                if ($count === 0) {
                    $parent->replaceChild($importedNode, $element);
                } else {
                    $parent->appendChild($importedNode);
                }

                $element = $importedNode;
                $count++;
            } catch (\Throwable $e) {
                error_log("Error importing element: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Process components with improved DOM querying and error handling
     */
    private function processComponents(\Dom\HTMLDocument|\Dom\XMLDocument $document, \Dom\XPath $xpath): void
    {
        $elements = $xpath->query('//*[@*[starts-with(name(), "data-v-component-")]]');
        if (!$elements) {
            return;
        }

        $components = [];
        $optionsMap = [];

        foreach ($elements as $element) {
            $this->extractComponentAttributes($element, $components, $optionsMap);
        }

        $cacheDriver = Cache::getInstance();
        // Process each component and apply extracted options
        foreach (array_keys($components) as $component) {
            $options = $optionsMap[$component] ?? [];
            $this->extractComponentData($component, $options, $cacheDriver);
        }
    }

    /**
     * Extract component attributes with improved parsing
     */
    private function extractComponentAttributes(\Dom\Element $element, array &$components, array &$optionsMap): void
    {
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
                $parsedOptions = $this->parseComponentOptions($attrValue);
                if ($parsedOptions !== null) {
                    $optionsMap[$optionComponent] = $parsedOptions;
                }
            }
        }
    }

    /**
     * Parse component options with improved JSON handling
     */
    private function parseComponentOptions(string $attrValue): ?array
    {
        $parsedOptions = json_decode($attrValue, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to fix common JSON issues
            $fixedValue = $this->fixJsonString($attrValue);
            $parsedOptions = json_decode($fixedValue, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decoding error in options: " . json_last_error_msg() . " for value: $attrValue");
                return null;
            }
        }

        return $parsedOptions;
    }

    /**
     * Fix common JSON string issues
     */
    private function fixJsonString(string $value): string
    {
        return preg_replace([
            '/([{,])\s*([a-zA-Z0-9_]+)\s*:/',  // Convert missing-quoted keys: {site_id: -> {"site_id":
            '/([{,])\s*\'([a-zA-Z0-9_]+)\'\s*:/', // Convert single-quoted keys: {'site_id': -> {"site_id":
            '/:\s*\'(.*?)\'(\s*[},\]])/',        // Convert single-quoted values: 'mango' -> "mango"
            '/:\s*([a-zA-Z_][a-zA-Z0-9_ ]*)(\s*[},\]])/'  // Convert unquoted string values (multi-word): site_id: sydney -> "sydney"
        ], [
            '$1"$2":',
            '$1"$2":',
            ': "$1"$2',
            ': "$1"$2'
        ], $value);
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
