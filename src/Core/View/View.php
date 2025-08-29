<?php

namespace App\Core\View;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\System\Component\Component;
use App\Core\System\Extensions\Plugin;
use App\Core\System\Extensions\Themes;
use App\Core\System\Sites;
use App\Core\System\Event;
use App\Core\System\Vtpl\Vtpl;
use Exception;
use function App\Core\System\utils\config;
use function App\Core\System\utils\filter;
use function App\Core\System\utils\isEditor;

#[\AllowDynamicProperties]
class View {

    private mixed $theme = null;
    private mixed $themePath = null;
    private mixed $plugin = null;
    private mixed $pluginPath = null;
    private mixed $template = null;
    private mixed $templatePath = null;
    private mixed $html = null;
    private mixed $htmlTemplate = null;
    private mixed $htmlPath = null;
    private bool $useComponent = true;
    private mixed $component = null;
    private mixed $componentContent = null;
    private mixed $compiledTemplate = null;

    public mixed $controller = null;
    private string $documentType = 'html';
    private string $type = 'html';
    private Vtpl $templateEngine;
    private mixed $serviceTemplate = null;
    private bool $isEditor = false;
    public array $parameters = [];

    /**
     * Initializes the View instance.
     * Sets the theme, template paths, and template engine.
     */
    public function __construct() {
        $this->theme = (APP === 'app') ? Sites::getTheme() ?? 'default' : config(APP . '.theme', 'default');
        $this->isEditor = isEditor();

        if (isset($_REQUEST['_component_ajax']) && $this->isEditor) {
            $this->component = filter('/[a-z\-]*/', $_REQUEST['_component_ajax'], 15);
            $this->componentCount = filter('/\d+/', $_REQUEST['_component_id'], 2);
            $this->componentContent = $_POST['_component_content'] ?? '';
            $this->html = $_POST['html'] ?? '';
        }

        $selector = isset($this->component) ? '[data-v-component-' . $this->component . ']' : null;

        $this->templateEngine = new Vtpl($selector, $this->componentCount ?? 0, $this->componentContent ?? '');
        $this->initializePaths();
        $this->initializeTemplateEnginePaths();

        if ($this->isEditor) {
            $this->templateEngine->removeVattrs(false);
        }
    }

    public function initializePaths(): void
    {
        $this->themePath = $this->getThemePath().DS;
        $this->htmlPath = $this->themePath;
        $this->templatePath = DIR_CORE. 'templates' . DS;
    }

    public function initializeTemplateEnginePaths(): void
    {
        $this->templateEngine->addTemplatePath($this->templatePath);
        $this->templateEngine->setHtmlPath($this->htmlPath);
    }

    /**
     * Sets a property dynamically.
     *
     * @param string $key The property name.
     * @param mixed $value The property value.
     */
    public function set(string $key, $value): void {
        $this->$key = $value;
    }

    public function setPlugin($plugin): void
    {
        if($plugin){
            $this->plugin = $plugin;
            $this->pluginPath = DIR_PLUGINS . $plugin . DS;
        }
    }


    /**
     * @param string $file
     * @param string|null $folder
     * @param string|null $plugin
     * @return void
     * @throws NotFoundHttpException
     */
    public function setPaths(string $file, ?string $folder=null, ?string $plugin = null): void {
        $this->setPlugin($plugin);
        $controller = $this->controller;

        $possibleHtmlPaths = [
            // Theme Path
            $plugin
                ? $this->themePath . 'plugins' . DS . $plugin . DS . $controller . DS . ($folder ? "$folder.$file" : $file)
                : $this->themePath . $controller . DS . ($folder ? "$folder.$file" : $file),
            //Without Controller
            $plugin
                ? $this->themePath . 'plugins' . DS . $plugin . DS . ($folder ? "$folder.$file" : $file)
                : $this->themePath . ($folder ? "$folder.$file" : $file),
            // Plugin Path
            $plugin ? $this->pluginPath . 'views' . DS . $controller . DS . ($folder ? "$folder.$file" : $file) : null,
            // Core Path
            DIR_CORE . 'views' . DS . $controller . DS . ($folder ? "$folder.$file" : $file),
            // Test Path - Current Directory
            __DIR__ . '/' . ($folder ? "$folder.$file" : $file),
        ];
        
        // Try the test path first (last in the array)
        $testHtmlPath = __DIR__ . '/' . ($folder ? "$folder.$file" : $file);
        try {
            if (is_file($testHtmlPath)) {
                $this->htmlTemplate = $testHtmlPath;
                $this->htmlPath = dirname($testHtmlPath) . '/';
                // HTML path found
            } else {
                // Fall back to other paths
                foreach ($possibleHtmlPaths as $path) {
                    if ($path && is_file($path)) {
                        $this->htmlTemplate = $path;
                        $this->htmlPath = dirname($path) . '/';
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            echo "❌ Error checking HTML path: " . $e->getMessage() . "\n";
        }
        
        if(!$this->htmlTemplate) throw new NotFoundHttpException("Template path not found: " . ($folder ? "$folder.$file" : $file));
        
        $tplFile = str_replace('.html', '.tpl', $file);
        $possibleTemplatePaths = [
            // Plugin Templates
            $plugin ? $this->pluginPath . 'templates' . DS . $controller . DS . ($folder ? "$folder.$tplFile" : $tplFile) : null,
            // Core Templates
            DIR_CORE . 'templates' . DS . $controller . DS . ($folder ? "$folder.$tplFile" : $tplFile),
            // Common Template Paths
            $plugin ? $this->pluginPath . 'templates' . DS . 'common.tpl' : null,
            DIR_CORE . 'templates' . DS . 'common.tpl',
            // Test Path - Current Directory
            __DIR__ . '/' . ($folder ? "$folder.$tplFile" : $tplFile),
        ];

        // Try the test template path first (last in the array)
        $testTemplatePath = __DIR__ . '/' . ($folder ? "$folder.$tplFile" : $tplFile);
        try {
            if (is_file($testTemplatePath)) {
                $this->template = $testTemplatePath;
                $this->templatePath = dirname($testTemplatePath) . '/';
                $this->templateEngine->addTemplatePath($this->templatePath);
                // Template path found
            } else {
                // Fall back to other paths
                foreach ($possibleTemplatePaths as $path) {
                    if ($path && is_file($path)) {
                        $this->template = $path;
                        $this->templatePath = dirname($path) . '/';
                        $this->templateEngine->addTemplatePath($this->templatePath);
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            echo "❌ Error checking template path: " . $e->getMessage() . "\n";
        }
        
        // Update the Vtpl engine's htmlPath to match our correct path
        $this->templateEngine->setHtmlPath($this->htmlPath);
        
        // Check if we found a template
        if (!$this->template) {
            throw new NotFoundHttpException("Template path not found: " . ($folder ? "$folder.$file" : $file));
        }
    }

    public function setCompiledTemplate(mixed $filename):void
    {
        $plugin = $this->plugin;
        // Construct the compiled filename
        $this->compiledTemplate = DIR_COMPILED_TEMPLATES
            . APP . '_' . (defined('SITE_ID') ? SITE_ID : '-') . '_'
            . ((is_null($this->component)) ? '' : $this->component . $this->componentCount . '_')
            . $this->theme . '_'
            . $this->controller . '_'
            . (!empty($plugin) ? 'plugins_'. $plugin . '_' : '')
            . str_replace([DS, '/', '\\'], '_', $filename)
            . ($this->isEditor ? '-edit' : '');
        $this->serviceTemplate = $this->compiledTemplate;
    }


    /**
     * Gets the document type.
     *
     * @return string The document type, e.g., 'html'.
     */
    public function getDocumentType(): string {
        return $this->documentType;
    }

    /**
     * Resolves the path for the specified template.
     *
     * @return string The resolved template path.
     */
    public function getThemePath(): string {
        return Themes::getThemePath();
    }
    /**
     * Resolves the path for the specified template.
     *
     * @return mixed The resolved template path.
     */
    public function getTemplateFilePath(): mixed {
        return $this->template;
    }

    function getTemplatePath(): mixed
    {
        return $this->templatePath;
    }

    public function getHtmlTemplate():mixed
    {
        return $this->htmlTemplate;
    }

    public function getCompiledTemplate():mixed
    {
        return $this->compiledTemplate;
    }


    /**
     * Gets the path to a service template.
     *
     * @param string $service The service name.
     * @return string The service template path.
     */
    public function serviceTemplate(mixed $service = null): string {
        return $this->serviceTemplate;
    }

    /**
     * Determines if a template requires recompilation.
     * @return bool True if recompilation is required, false otherwise.
     */
    private function needsRecompile(): bool {
        $templateMtime = file_exists($this->template) ? filemtime($this->template) : 0;
        $htmlMtime = file_exists($this->htmlTemplate) ? filemtime($this->htmlTemplate) : 0;
        $compiledMtime = file_exists($this->compiledTemplate) ? filemtime($this->compiledTemplate) : 0;

        return max($templateMtime, $htmlMtime) > $compiledMtime || !file_exists($this->compiledTemplate);
    }

    private function prepareComponentsData($plugin, $regenerate=false): void
    {
        if ($this->useComponent && ! defined('CLI')) {
            if ($plugin) {
                new Component($this, $regenerate, $this->componentContent);
            } else {
                Component::getInstance($this, $regenerate, $this->componentContent);
            }
        }
    }

    /**
     * Compiles the specified template file.
     *
     * @param mixed|null $plugin Optional service parameter.
     * @param bool $regenerate Optional service parameter.
     * @throws Exception If the template file cannot be loaded or saved.
     */
    public function compile(mixed $plugin = null, bool $regenerate = false): void {
        $this->prepareComponentsData($plugin, $regenerate);
        if (!file_exists($this->template)) {
            throw new Exception("Template file not found: $this->template");
        }

        if (!@touch($this->htmlTemplate)) {
            throw new Exception("Failed to touch file: $this->htmlTemplate");
        }



        Event::trigger(
            __CLASS__,
            __FUNCTION__,
            $this->htmlTemplate,
            $this->template,
            $this->compiledTemplate,
            $this->templateEngine,
            $this
        );

        $errors = $this->html
            ? $this->templateEngine->loadHtml($this->html)
            : $this->templateEngine->loadHtmlTemplate($this->htmlTemplate);



        // Check for errors: loadHtml returns array (empty = success), loadHtmlTemplate returns true/false or array with errors
        if (is_array($errors) && count($errors) > 0) {
            throw new Exception("Failed to load template: $this->htmlTemplate");
        } elseif ($errors === false) {
            throw new Exception("Failed to load template: $this->htmlTemplate");
        }

        $this->templateEngine->loadTemplateFileFromPath(basename($this->template));

        Event::trigger(
            __CLASS__,
            __FUNCTION__ . ':after',
            $this->htmlTemplate,
            $this->template,
            $this->compiledTemplate,
            $this->templateEngine,
            $this
        );
        try {
            $this->templateEngine->saveCompiledTemplate($this->compiledTemplate);
        }catch (\Exception $e) {
            throw new Exception("Failed to load template: $this->htmlTemplate");
        }
    }

    function fragment($selector, $index = 0): void {
        $this->component      = $selector;
        $this->componentCount = $index;
    }
    /**
     * Sets the output type (e.g., 'html' or 'json').
     *
     * @param string $type The output type.
     */
    public function setType(string $type): void {
        $this->type = $type;
    }

    /**
     * Gets the current output type.
     *
     * @return string The output type.
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Renders the specified template with the provided data.
     *
     * @param string $template The template name.
     * @param array $data The data to render.
     * @return string The rendered output.
     * @throws Exception If the output type is unsupported.
     */
    public function render(string $template, array $data = [], string $plugin = ''): string {
        $this->parameters = $data;
        // Convert dot-separated template path to directory-separated path
        $templatePath = str_replace('.', DIRECTORY_SEPARATOR, $template) . '.html';
        $folder = dirname($templatePath);
        $folder = $folder=='.'?null:$folder;
        $file = basename($templatePath);

        try {
            $this->setPaths($file, $folder, $plugin);
        } catch (NotFoundHttpException $e) {
            throw new NotFoundHttpException("Template not found: $template");
        }
        $this->setCompiledTemplate($file);
        Event::trigger(__CLASS__, __FUNCTION__, $file, $folder, $this);

        // Get the compiled path of the template
        $recompile = $this->needsRecompile();
        if($recompile){
            try {
                $this->compile($plugin, true);
            } catch (Exception $e) {
                throw new Exception("Failed to compile template: $template");
            }
        }else{
            $this->prepareComponentsData($plugin);
        }

        // Extract data into variables
        extract($data);

        // Render based on output type
        if ($this->type === 'html') {
            ob_start();
            include $this->compiledTemplate;
            return ob_get_clean();
        } elseif ($this->type === 'json') {
            return json_encode($data);
        }

        throw new Exception("Unsupported type: {$this->type}");
    }
}
