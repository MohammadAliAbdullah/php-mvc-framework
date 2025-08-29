<?php

declare(strict_types=1);

namespace App\Core\Validation;

class ProductDataValidation
{
    public ?int $product_id = null;
    public ?int $km_item_id = null;
    public ?int $product_type_id = null;
    public ?int $class_id = null;
    public ?int $company_id = null;
    public ?int $admin_id = null;
    public ?int $parent_id = null;
    public ?string $model = null;
    public ?string $description = null;
    public ?string $specifications = null;
    public ?string $warranty_period = null;
    public ?string $product_code = null;
    public ?string $factory_code = null;
    public ?string $sku = null;
    public ?string $isbn = null;
    public ?string $barcode = null;
    public bool $track_stock = false;
    public ?int $stock_quantity = null;
    public ?int $stock_status_id = null;
    public ?int $lead_days = null;
    public ?int $melbourne_lead_days = null;
    public ?int $safety_stock = null;
    public ?int $qty_alert = null;
    public ?string $image = null;
    public ?int $manufacturer_id = null;
    public ?int $vendor_id = null;
    public ?int $import_vendor_id = null;
    public ?int $factory_vendor_id = null;
    public ?int $product_range_id = null;
    public ?int $product_category_id = null;
    public ?int $edgetape_colour_id = null;
    public bool $requires_shipping = true;
    public ?int $tax_type_id = null;
    public ?string $material = null;
    public ?float $weight = null;
    public ?int $weight_type_id = null;
    public ?float $length = null;
    public ?int $length_type_id = null;
    public ?float $width = null;
    public ?float $height = null;
    public ?float $depth = null;
    public ?float $price = null;
    public ?float $old_price = null;
    public ?int $min_order_quantity = null;
    public ?string $out_of_stock_status = null;
    public ?float $carton_qm = null;
    public ?string $size = null;
    public ?float $carton_width = null;
    public ?float $carton_depth = null;
    public ?float $carton_height = null;
    public ?float $gross_weight = null;
    public ?string $date_available = null;
    public ?string $template = null;
    public ?int $views = null;
    public bool $subtract_stock = true;
    public bool $status = false;
    public bool $is_featured = false;
    public ?int $sort_order = null;
    public ?int $project_price_qty = null;
    public ?float $project_price_discount = null;
    public bool $active = true;
    public bool $archive = false;
    
    // Additional fields from CSV
    public ?string $specifications_image = null;
    public ?string $banner_image = null;
    public ?string $video_link = null;
    public ?string $image_thumb = null;
    public ?string $main_image_one = null;
    public ?string $main_image_one_title = null;
    public ?string $main_image_one_description = null;
    public ?string $main_image_two = null;
    public ?string $main_image_two_title = null;
    public ?string $main_image_two_description = null;
    public ?string $feature_description = null;
    public ?string $feature_image_one = null;
    public ?string $feature_image_one_title = null;
    public ?string $feature_image_one_description = null;
    public ?string $feature_image_two = null;
    public ?string $feature_image_two_title = null;
    public ?string $feature_image_two_description = null;
    public ?string $feature_image_three = null;
    public ?string $feature_image_three_title = null;
    public ?string $feature_image_three_description = null;

    private array $errors = [];

    public array $categories;
    public array $categories_data;
    /**
     * @param array $categories = [ 'Workstations' => 1, 'Screens' => 2, 'Gaming' => 3, ... ]
     */

    public function __construct(array $data, array $categories = [])
    {
        // Check if category_one isset or .... 

        // If isset then find category id from $categories 

        // Check if product_id exsit then set $this->categores[] = [ 'category_id' => $category_id, 'product_id' => $product_id ]

        //If not product_id then use unique column which is product_code and set $this->categories_data[] = [ 'category_id' => $category_id, 'product_code' => $product_code ]
        
        // Then later in the repository after inserting product update each category_id with product_id by filtering id using product_code (Follow the post repository for reference)
        
        // Change total structure and follow the post repository for reference


        $this->product_id = $this->validateInteger($data['product_id'] ?? null, 'product_id');
        $this->km_item_id = $this->validateInteger($data['km_item_id'] ?? null, 'km_item_id', 0);
        $this->product_type_id = $this->validateInteger($data['product_type_id'] ?? null, 'product_type_id', 1);
        $this->class_id = $this->validateInteger($data['class_id'] ?? null, 'class_id', 1);
        $this->company_id = $this->validateInteger($data['company_id'] ?? null, 'company_id', 1);
        $this->admin_id = $this->validateInteger($data['admin_id'] ?? null, 'admin_id', 1);
        $this->parent_id = $this->validateInteger($data['parent_id'] ?? null, 'parent_id');
        $this->model = $this->validateString($data['model'] ?? null, 'model', 64);
        $this->description = $this->validateString($data['description'] ?? null, 'description', 500);
        $this->specifications = $this->validateString($data['specifications'] ?? null, 'specifications', 1000);
        $this->warranty_period = $this->validateString($data['warranty_period'] ?? null, 'warranty_period', 10);
        $this->product_code = $this->validateString($data['product_code'] ?? null, 'product_code', 50);
        $this->factory_code = $this->validateString($data['factory_code'] ?? null, 'factory_code', 255);
        $this->sku = $this->validateString($data['sku'] ?? null, 'sku', 64);
        $this->isbn = $this->validateString($data['isbn'] ?? null, 'isbn', 17);
        $this->barcode = $this->validateString($data['barcode'] ?? null, 'barcode', 13);
        $this->track_stock = $this->validateBoolean($data['track_stock'] ?? null, 'track_stock');
        $this->stock_quantity = $this->validateInteger($data['stock_quantity'] ?? null, 'stock_quantity', 0);
        $this->stock_status_id = $this->validateInteger($data['stock_status_id'] ?? null, 'stock_status_id', 1);
        $this->lead_days = $this->validateInteger($data['lead_days'] ?? null, 'lead_days', 0);
        $this->melbourne_lead_days = $this->validateInteger($data['melbourne_lead_days'] ?? null, 'melbourne_lead_days', 0);
        $this->safety_stock = $this->validateInteger($data['safety_stock'] ?? null, 'safety_stock', 0);
        $this->qty_alert = $this->validateInteger($data['qty_alert'] ?? null, 'qty_alert', 0);
        $this->image = $this->validateJson($data['image'] ?? null, 'image');
        $this->manufacturer_id = $this->validateInteger($data['manufacturer_id'] ?? null, 'manufacturer_id');
        $this->vendor_id = $this->validateInteger($data['vendor_id'] ?? null, 'vendor_id');
        $this->import_vendor_id = $this->validateInteger($data['import_vendor_id'] ?? null, 'import_vendor_id');
        $this->factory_vendor_id = $this->validateInteger($data['factory_vendor_id'] ?? null, 'factory_vendor_id');
        $this->product_range_id = $this->validateInteger($data['product_range_id'] ?? null, 'product_range_id');
        $this->product_category_id = $this->validateInteger($data['product_category_id'] ?? null, 'product_category_id', 1);
        $this->edgetape_colour_id = $this->validateInteger($data['edgetape_colour_id'] ?? null, 'edgetape_colour_id');
        $this->requires_shipping = $this->validateBoolean($data['requires_shipping'] ?? null, 'requires_shipping');
        $this->tax_type_id = $this->validateInteger($data['tax_type_id'] ?? null, 'tax_type_id');
        $this->material = $this->validateString($data['material'] ?? null, 'material', 64);
        $this->weight = $this->validateFloat($data['weight'] ?? null, 'weight', 0.0);
        $this->weight_type_id = $this->validateInteger($data['weight_type_id'] ?? null, 'weight_type_id');
        $this->length = $this->validateFloat($data['length'] ?? null, 'length', 0.0);
        $this->length_type_id = $this->validateInteger($data['length_type_id'] ?? null, 'length_type_id');
        $this->width = $this->validateFloat($data['width'] ?? null, 'width');
        $this->height = $this->validateFloat($data['height'] ?? null, 'height');
        $this->depth = $this->validateFloat($data['depth'] ?? null, 'depth');
        $this->price = $this->validateFloat($data['price'] ?? null, 'price');
        $this->old_price = $this->validateFloat($data['old_price'] ?? null, 'old_price');
        $this->min_order_quantity = $this->validateInteger($data['min_order_quantity'] ?? null, 'min_order_quantity', 1);
        $this->out_of_stock_status = $this->validateString($data['out_of_stock_status'] ?? null, 'out_of_stock_status', 100);
        $this->carton_qm = $this->validateFloat($data['carton_qm'] ?? null, 'carton_qm');
        $this->size = $this->validateString($data['size'] ?? null, 'size', 255);
        $this->carton_width = $this->validateFloat($data['carton_width'] ?? null, 'carton_width', 0.0);
        $this->carton_depth = $this->validateFloat($data['carton_depth'] ?? null, 'carton_depth', 0.0);
        $this->carton_height = $this->validateFloat($data['carton_height'] ?? null, 'carton_height', 0.0);
        $this->gross_weight = $this->validateFloat($data['gross_weight'] ?? null, 'gross_weight');
        $this->date_available = $this->validateString($data['date_available'] ?? null, 'date_available', 255);
        $this->template = $this->validateString($data['template'] ?? null, 'template', 191);
        $this->views = $this->validateInteger($data['views'] ?? null, 'views', 0);
        $this->subtract_stock = $this->validateBoolean($data['subtract_stock'] ?? null, 'subtract_stock');
        $this->status = $this->validateBoolean($data['status'] ?? null, 'status');
        $this->is_featured = $this->validateBoolean($data['is_featured'] ?? null, 'is_featured');
        $this->sort_order = $this->validateInteger($data['sort_order'] ?? null, 'sort_order', 0);
        $this->project_price_qty = $this->validateInteger($data['project_price_qty'] ?? null, 'project_price_qty');
        $this->project_price_discount = $this->validateFloat($data['project_price_discount'] ?? null, 'project_price_discount', 0.0);
        $this->active = $this->validateBoolean($data['active'] ?? null, 'active');
        $this->archive = $this->validateBoolean($data['archive'] ?? null, 'archive');
        
        // Additional fields
        $this->specifications_image = $this->validateJson($data['specifications_image'] ?? null, 'specifications_image');
        $this->banner_image = $this->validateJson($data['banner_image'] ?? null, 'banner_image');
        $this->video_link = $this->validateString($data['video_link'] ?? null, 'video_link', 191);
        $this->image_thumb = $this->validateJson($data['image_thumb'] ?? null, 'image_thumb');
        $this->main_image_one = $this->validateJson($data['main_image_one'] ?? null, 'main_image_one');
        $this->main_image_one_title = $this->validateString($data['main_image_one_title'] ?? null, 'main_image_one_title', 191);
        $this->main_image_one_description = $this->validateText($data['main_image_one_description'] ?? null, 'main_image_one_description');
        $this->main_image_two = $this->validateJson($data['main_image_two'] ?? null, 'main_image_two');
        $this->main_image_two_title = $this->validateString($data['main_image_two_title'] ?? null, 'main_image_two_title', 191);
        $this->main_image_two_description = $this->validateText($data['main_image_two_description'] ?? null, 'main_image_two_description');
        $this->feature_description = $this->validateText($data['feature_description'] ?? null, 'feature_description');
        $this->feature_image_one = $this->validateJson($data['feature_image_one'] ?? null, 'feature_image_one');
        $this->feature_image_one_title = $this->validateString($data['feature_image_one_title'] ?? null, 'feature_image_one_title', 191);
        $this->feature_image_one_description = $this->validateText($data['feature_image_one_description'] ?? null, 'feature_image_one_description');
        $this->feature_image_two = $this->validateJson($data['feature_image_two'] ?? null, 'feature_image_two');
        $this->feature_image_two_title = $this->validateString($data['feature_image_two_title'] ?? null, 'feature_image_two_title', 191);
        $this->feature_image_two_description = $this->validateText($data['feature_image_two_description'] ?? null, 'feature_image_two_description');
        $this->feature_image_three = $this->validateJson($data['feature_image_three'] ?? null, 'feature_image_three');
        $this->feature_image_three_title = $this->validateString($data['feature_image_three_title'] ?? null, 'feature_image_three_title', 191);
        $this->feature_image_three_description = $this->validateText($data['feature_image_three_description'] ?? null, 'feature_image_three_description');
    }

    private function validateInteger($value, string $field, ?int $default = null): ?int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        
        if (is_numeric($value)) {
            $intValue = (int) $value;
            if ($intValue >= 0) {
                return $intValue;
            }
        }
        
        $this->addError($field, "Field '$field' must be a valid positive integer");
        return $default;
    }

    private function validateFloat($value, string $field, ?float $default = null): ?float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        
        if (is_numeric($value)) {
            $floatValue = (float) $value;
            if ($floatValue >= 0) {
                return $floatValue;
            }
        }
        
        $this->addError($field, "Field '$field' must be a valid positive number");
        return $default;
    }

    private function validateString($value, string $field, int $maxLength): ?string
    {
        if ($value === null) {
            return '';
        }
        
        $stringValue = (string) $value;
        
        if (strlen($stringValue) > $maxLength) {
            $this->addError($field, "Field '$field' must not exceed $maxLength characters");
            return substr($stringValue, 0, $maxLength);
        }
        
        return $stringValue;
    }

    private function validateText($value, string $field): ?string
    {
        if ($value === null) {
            return '';
        }
        
        return (string) $value;
    }

    private function validateSlug($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        if (empty($slug)) {
            return null;
        }
        
        return $slug;
    }

    private function validateJson($value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_string($value)) {
            // Check if it's already valid JSON
            if ($this->isValidJson($value)) {
                return $value;
            }
            
            // If not JSON, create a simple JSON structure
            $jsonData = [
                [
                    'objectURL' => $value,
                    'name' => basename($value),
                    'type' => 'image/jpeg'
                ]
            ];
            
            $jsonResult = json_encode($jsonData);
            if ($jsonResult !== false) {
                return $jsonResult;
            }
        }
        
        $this->addError($field, "Field '$field' must be a valid JSON string or image path");
        return null;
    }

    private function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function validateBoolean($value, string $field): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        if (is_string($value)) {
            $lowerValue = strtolower(trim($value));
            if (in_array($lowerValue, ['true', '1', 'yes', 'on'])) {
                return true;
            }
            if (in_array($lowerValue, ['false', '0', 'no', 'off'])) {
                return false;
            }
        }
        
        $this->addError($field, "Field '$field' must be a valid boolean value");
        return false;
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    public function validate(): bool|array
    {
        if (empty($this->errors)) {
            return $this->toArray();
        }
        
        return false;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'km_item_id' => $this->km_item_id,
            'product_type_id' => $this->product_type_id,
            'class_id' => $this->class_id,
            'company_id' => $this->company_id,
            'admin_id' => $this->admin_id,
            'parent_id' => $this->parent_id,
            'model' => $this->model,
            'description' => $this->description,
            'specifications' => $this->specifications,
            'warranty_period' => $this->warranty_period,
            'product_code' => $this->product_code,
            'factory_code' => $this->factory_code,
            'sku' => $this->sku,
            'isbn' => $this->isbn,
            'barcode' => $this->barcode,
            'track_stock' => $this->track_stock ? 1 : 0,
            'stock_quantity' => $this->stock_quantity,
            'stock_status_id' => $this->stock_status_id,
            'lead_days' => $this->lead_days,
            'melbourne_lead_days' => $this->melbourne_lead_days,
            'safety_stock' => $this->safety_stock,
            'qty_alert' => $this->qty_alert,
            'image' => $this->image,
            'manufacturer_id' => $this->manufacturer_id,
            'vendor_id' => $this->vendor_id,
            'import_vendor_id' => $this->import_vendor_id,
            'factory_vendor_id' => $this->factory_vendor_id,
            'product_range_id' => $this->product_range_id,
            'product_category_id' => $this->product_category_id,
            'edgetape_colour_id' => $this->edgetape_colour_id,
            'requires_shipping' => $this->requires_shipping ? 1 : 0,
            'tax_type_id' => $this->tax_type_id,
            'material' => $this->material,
            'weight' => $this->weight,
            'weight_type_id' => $this->weight_type_id,
            'length' => $this->length,
            'length_type_id' => $this->length_type_id,
            'width' => $this->width,
            'height' => $this->height,
            'depth' => $this->depth,
            'price' => $this->price,
            'old_price' => $this->old_price,
            'min_order_quantity' => $this->min_order_quantity,
            'out_of_stock_status' => $this->out_of_stock_status,
            'carton_qm' => $this->carton_qm,
            'size' => $this->size,
            'carton_width' => $this->carton_width,
            'carton_depth' => $this->carton_depth,
            'carton_height' => $this->carton_height,
            'gross_weight' => $this->gross_weight,
            'date_available' => $this->date_available,
            'template' => $this->template,
            'views' => $this->views,
            'subtract_stock' => $this->subtract_stock ? 1 : 0,
            'status' => $this->status ? 1 : 0,
            'is_featured' => $this->is_featured ? 1 : 0,
            'sort_order' => $this->sort_order,
            'project_price_qty' => $this->project_price_qty,
            'project_price_discount' => $this->project_price_discount,
            'active' => $this->active ? 1 : 0,
            'archive' => $this->archive ? 1 : 0,
            'specifications_image' => $this->specifications_image,
            'banner_image' => $this->banner_image,
            'video_link' => $this->video_link,
            'image_thumb' => $this->image_thumb,
            'main_image_one' => $this->main_image_one,
            'main_image_one_title' => $this->main_image_one_title,
            'main_image_one_description' => $this->main_image_one_description,
            'main_image_two' => $this->main_image_two,
            'main_image_two_title' => $this->main_image_two_title,
            'main_image_two_description' => $this->main_image_two_description,
            'feature_description' => $this->feature_description,
            'feature_image_one' => $this->feature_image_one,
            'feature_image_one_title' => $this->feature_image_one_title,
            'feature_image_one_description' => $this->feature_image_one_description,
            'feature_image_two' => $this->feature_image_two,
            'feature_image_two_title' => $this->feature_image_two_title,
            'feature_image_two_description' => $this->feature_image_two_description,
            'feature_image_three' => $this->feature_image_three,
            'feature_image_three_title' => $this->feature_image_three_title,
            'feature_image_three_description' => $this->feature_image_three_description
        ];
    }

    public function getUniqueIdentifier(): string
    {
        if (!empty($this->product_code)) {
            return 'product_code_' . $this->product_code;
        }
        
        if (!empty($this->product_id)) {
            return 'product_id_' . $this->product_id;
        }
        
        if (!empty($this->sku)) {
            return 'sku_' . $this->sku;
        }
        
        return 'unique_' . uniqid();
    }
}
