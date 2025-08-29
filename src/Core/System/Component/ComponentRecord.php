<?php

declare(strict_types=1);

namespace App\Core\System\Component;

class ComponentRecord
{

    private ?string $_hash;
    private bool $_designOnly=false;
    private ?string $_namespace;
    private ?string $_class;
    private array $_options=[];
    private array $_validOptions=[];
    private ?string $_filePath;
    private ?string $_cacheKey;
    private mixed $_cacheExpire;
    private mixed $_data; // Can hold any type (array, object, string, etc.)
    /**
     * Constructor to initialize component properties
     *
     * @param array $data Component initialization data
     */
    public function __construct(array $data = []) {
        $this->_namespace = $data['namespace'] ?? null;
        $this->_class = $data['class'] ?? null;
        $this->_validOptions = $data['validOptions'] ?? [];
        $this->_options = $data['options'] ?? [];
        $this->_filePath = $data['filePath'] ?? null;
        $this->_cacheKey = $data['cacheKey'] ?? null;
        $this->_cacheExpire = $data['cacheExpire'] ?? null;
        $this->_data = $data['data'] ?? [];
        $this->_designOnly = $data['designOnly'] ?? false;

        // Generate hash based on component data
        $this->generateHash();
    }

    /**
     * Generates a unique hash based on the component properties
     */
    private function generateHash(): void
    {
        $this->_hash = md5(serialize([
            $this->_class,
            $this->_options,
            $this->_filePath,
            $this->_cacheKey,
            $this->_cacheExpire,
            $this->_data
        ]));
    }
    /**
     * Get a property value
     *
     * @param string $property Property name
     * @return mixed|null Property value or null if not found
     */
    public function get(string $property): mixed
    {
        return $this->{'_'.$property} ?? null;
    }

    /**
     * Set a property value and regenerate hash
     *
     * @param string $property Property name
     * @param mixed $value Property value
     */
    public function set($property, $value): void
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
            $this->generateHash(); // Regenerate hash when properties change
        }
    }
    /**
     * Get the unique hash for this component
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->_hash;
    }

    public function toArray(): array
    {
        return [
            'namespace' => $this->_namespace,
            'cacheKey' => $this->_cacheKey,
            'cacheKeyExpire' => $this->_cacheExpire,
            'class' => $this->_class,
            'validOptions' => $this->_validOptions,
            'options' => $this->_options,
            'filePath' => $this->_filePath,
            'data' => $this->_data,
            'designOnly' => $this->_designOnly,
            'hash' => $this->_hash,
        ];
    }
}
