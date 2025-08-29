<?php

/**
 * SA Technology
 *
 * Copyright (C) 2025  Shofiul Alam
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

namespace App\Core\Components;

use App\Core\System\Component\ComponentBase;
use App\Core\System\Event;
use App\Core\Repositories\Component\ComponentRepositoryInterface;
use App\Core\Repositories\Post\PostRepositoryInterface;

class Blogmain extends ComponentBase {
    public static $defaultOptions = [
        'site_id'     => null,
        'language_id' => null,
    ];

    protected $options = [];

    public $cacheExpire = 0; //seconds

    private ComponentRepositoryInterface $componentRepository;
    private PostRepositoryInterface $postRepository;
    public function __construct(
        ComponentRepositoryInterface $componentRepository,
        PostRepositoryInterface $postRepository,
        array $options = []
    ) {
        parent::__construct($options);
        $this->componentRepository = $componentRepository;
        $this->postRepository = $postRepository;
    }
    function cacheKey() {
        //disable caching
        return false;
    }
    public static function getComponentMeta()
    {
        return [
            'name' => 'blogmain',
            'class' => self::class,
            'validOptions' => [
                'component_id'
            ],
            'filePath' => __FILE__,
            'cacheKey' => null,
            'data' => [],
            'designOnly' => false
        ];
    }

    function results($params = []) {
        // $results = [];
        // $results['section_title'] = 'Project Details';
        // $results['section_subtitle'] = "Pellentesque sodales lacinia diam, vel lacinia lectus ultrices id. Maecenas viverra justo nec mauris pulvinar dignissim. Proin quis arcu eu velit vehicula fermentum. Ut pharetra, nunc sed fringilla fermentum, nulla eros rhoncus felis, ac varius ligula quam nec nisl. Proin ac tellus sed dui ultrices tempus. Duis ut arcu et diam lacinia cursus nec non justo";
        
        // $results['section_subtitle2'] = "Aliquam malesuada tortor ut dolor suscipit, id convallis ipsum lacinia. Curabitur feugiat lectus non sem ullamcorper, nec finibus nulla consequat. Sed nec odio magna. Aliquam non libero ultricies, efficitur nulla id, ultricies urna. Nullam at placerat justo, non fermentum magna. Nullam nec libero odio. Sed eleifend euismod tellus, ut fermentum libero pellentesque a. Sed eget nulla et mi consectetur vestibulum. Phasellus at leo eu orci vestibulum tincidunt. Vestibulum fringilla mi id felis varius ultricies. Donec nec suscipit nulla. Vivamus vitae diam purus.";

        // $results['img'] = '/img/blog-detail/blog-main.png';

        // $results['section_subtitle3'] = "Sed nec odio magna. Aliquam non libero ultricies, efficitur nulla id, ultricies urna. Nullam at placerat justo, non fermentum magna. Nullam nec libero odio. Sed eleifend euismod tellus, ut fermentum libero pellentesque a. Sed eget nulla et mi consectetur vestibulum. Phasellus at leo eu orci vestibulum tincidunt. Vestibulum fringilla mi id felis varius ultricies. Donec nec suscipit nulla. Vivamus vitae diam purus.";
        // $results['section_subtitle4'] = "Sed nec odio magna. Aliquam non libero ultricies, efficitur nulla id, ultricies urna. Nullam at placerat justo, non fermentum magna. Nullam nec libero odio. Sed eleifend euismod tellus, ut fermentum libero pellentesque a. Sed eget nulla et mi consectetur vestibulum. Phasellus at leo eu orci vestibulum tincidunt. Vestibulum fringilla mi id felis varius ultricies. Donec nec suscipit nulla. Vivamus vitae diam purus.";

        // return $results;

        
        $component = $this->componentRepository->getComponentByName('blogmain');
        if(isset($component->items[0])){
            $item = $component->items[0];
            $params['model'] = $item['model'];
            $params['fields'] = $item['fields'];
            $params['item_count'] = $item['item_count'];
            foreach($item['related_models'] as $joinModel){
                $params['joins'][] = [ $joinModel['type'], $joinModel['source'], ' = ' ,$joinModel['model']];
            }
        }

        $componentItems = [];

        $componentItems['section_title'] = $component->section_title ?? $component->title?? '';
        $componentItems['section_subtitle'] = $component->section_subtitle ?? $component->subtitle?? '';
        $results = $this->postRepository->getBlogMainComponentData($params);
        
        $results['section_title'] = $componentItems['section_title'];
        $results['section_subtitle'] = $componentItems['section_subtitle'];
        $results['section_subtitle2'] = $results['section_subtitle2'];
        $results['section_subtitle3'] = $results['section_subtitle3'];
        $results['section_subtitle4'] = $results['section_subtitle4'];
        $results['img'] = $results['img'];

        list($component) = Event::trigger(__CLASS__,__FUNCTION__, $component);
        $results['options'] = $this->options;
       

        return $results;
    }
}
