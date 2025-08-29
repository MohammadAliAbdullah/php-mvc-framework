<?php

/**
 * Generate main HTML content using DOMDocument
 * This replaces the template-based approach with programmatic HTML generation
 */
class HomePageGenerator
{
    private DOMDocument $document;
    private DOMElement $mainElement;
    
    public function __construct()
    {
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
        $this->document->preserveWhiteSpace = false;
    }
    
    /**
     * Generate the main HTML content
     */
    public function generateMainContent(string $title = "Title"): string
    {
        // Create main element
        $this->mainElement = $this->document->createElement('main');
        $this->mainElement->setAttribute('role', 'main');
        $this->mainElement->setAttribute('class', 'inner cover');
        
        // Create heading
        $heading = $this->document->createElement('h1');
        $heading->setAttribute('class', 'cover-heading');
        $heading->textContent = $title;
        
        // Create first paragraph
        $paragraph1 = $this->document->createElement('p');
        $paragraph1->setAttribute('class', 'lead');
        $paragraph1->textContent = 'Cover is a one-page template for building simple and beautiful home pages. Download, edit the text, and add your own fullscreen background photo to make it your own.';
        
        // Create second paragraph with link
        $paragraph2 = $this->document->createElement('p');
        $paragraph2->setAttribute('class', 'lead');
        
        $link = $this->document->createElement('a');
        $link->setAttribute('href', '#');
        $link->setAttribute('class', 'btn btn-lg btn-secondary');
        $link->textContent = 'Learn more';
        
        $paragraph2->appendChild($link);
        
        // Append all elements to main
        $this->mainElement->appendChild($heading);
        $this->mainElement->appendChild($paragraph1);
        $this->mainElement->appendChild($paragraph2);
        
        // Append main to document
        $this->document->appendChild($this->mainElement);
        
        return $this->document->saveHTML($this->mainElement);
    }
    
    /**
     * Generate complete HTML document
     */
    public function generateCompleteHTML(string $title = "Title"): string
    {
        // Create HTML structure
        $html = $this->document->createElement('html');
        $html->setAttribute('lang', 'en');
        
        $head = $this->document->createElement('head');
        $meta = $this->document->createElement('meta');
        $meta->setAttribute('charset', 'UTF-8');
        $meta->setAttribute('name', 'viewport');
        $meta->setAttribute('content', 'width=device-width, initial-scale=1.0');
        
        $titleElement = $this->document->createElement('title');
        $titleElement->textContent = $title;
        
        $head->appendChild($meta);
        $head->appendChild($titleElement);
        
        $body = $this->document->createElement('body');
        
        // Generate main content
        $mainContent = $this->generateMainContent($title);
        
        // Create a temporary document to parse the main content
        $tempDoc = new DOMDocument();
        $tempDoc->loadHTML($mainContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // Import the main content into the body
        $importedMain = $this->document->importNode($tempDoc->documentElement, true);
        $body->appendChild($importedMain);
        
        $html->appendChild($head);
        $html->appendChild($body);
        
        $this->document->appendChild($html);
        
        return $this->document->saveHTML();
    }
    
    /**
     * Get the DOMDocument instance for further manipulation
     */
    public function getDocument(): DOMDocument
    {
        return $this->document;
    }
    
    /**
     * Get the main element for further manipulation
     */
    public function getMainElement(): DOMElement
    {
        return $this->mainElement;
    }
}

// Example usage
if (php_sapi_name() === 'cli') {
    // CLI usage
    $generator = new HomePageGenerator();
    echo $generator->generateMainContent("My Custom Title");
} else {
    // Web usage - you can use this in your controller
    $generator = new HomePageGenerator();
    $html = $generator->generateMainContent("My Custom Title");
    
    // Output the HTML
    echo $html;
}

