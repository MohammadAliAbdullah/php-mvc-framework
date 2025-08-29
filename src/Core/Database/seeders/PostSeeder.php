<?php

declare(strict_types=1);

namespace App\Core\Database\seeders;

use App\Core\App\KernelCli;
use App\Core\Repositories\Post\PostRepositoryInterface;

use Illuminate\Container\Container;

class PostSeeder
{
    private PostRepositoryInterface $repository;
    private KernelCli $kernel;
    private Container $container;
    private $posts = [
        [
            'post_id' => 1,
            'admin_id' => 1,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-1.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 1,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 5,
            'views' => 1250,
            'description' => 'Exploring the latest trends in modern office design and how they enhance productivity and employee satisfaction.'
        ],
        [
            'post_id' => 2,
            'admin_id' => 1,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-2.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 2,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 8,
            'views' => 2100,
            'description' => 'A comprehensive guide to sustainable construction practices and their impact on the environment.'
        ],
        [
            'post_id' => 3,
            'admin_id' => 2,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-3.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 3,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 12,
            'views' => 3400,
            'description' => 'Essential tips and tricks for planning and executing a successful kitchen renovation project.'
        ],
        [
            'post_id' => 4,
            'admin_id' => 2,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-4.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 4,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 6,
            'views' => 1800,
            'description' => 'Discover the latest bathroom design trends that combine functionality with aesthetic appeal.'
        ],
        [
            'post_id' => 5,
            'admin_id' => 3,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-5.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 5,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 9,
            'views' => 2700,
            'description' => 'A complete guide to lighting design principles for residential and commercial spaces.'
        ],
        [
            'post_id' => 6,
            'admin_id' => 3,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-1.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 6,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 15,
            'views' => 4200,
            'description' => 'Comparing different flooring options to help you choose the perfect material for your space.'
        ],
        [
            'post_id' => 7,
            'admin_id' => 1,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-2.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 7,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 11,
            'views' => 3100,
            'description' => 'Exploring the integration of smart home technology in modern construction and renovation projects.'
        ],
        [
            'post_id' => 8,
            'admin_id' => 2,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-3.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 8,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 7,
            'views' => 1950,
            'description' => 'Designing beautiful and functional outdoor living spaces that extend your home\'s living area.'
        ],
        [
            'post_id' => 9,
            'admin_id' => 3,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-4.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 9,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 13,
            'views' => 3600,
            'description' => 'Understanding color psychology and how it influences interior design decisions.'
        ],
        [
            'post_id' => 10,
            'admin_id' => 1,
            'site_id' => 1,
            'status' => 'publish',
            'image' => '{"objectURL": "/img/bg/home/home_blog-5.jpg"}',
            'comment_status' => 'open',
            'password' => '',
            'parent' => 0,
            'sort_order' => 10,
            'type' => 'post',
            'template' => 'default',
            'comment_count' => 4,
            'views' => 1400,
            'description' => 'Best practices for project management in construction and renovation projects.'
        ]
    ];

    private $postContents = [
        [
            'post_id' => 1,
            'language_id' => 1,
            'name' => 'Modern Office Design: Creating Productive Workspaces',
            'slug' => 'modern-office-design-creating-productive-workspaces',
            'content' => '<p>Modern office design has evolved significantly over the past decade, moving away from traditional cubicle layouts toward more open, collaborative spaces that prioritize employee well-being and productivity.</p><p>Key elements of modern office design include:</p><ul><li>Open floor plans that encourage collaboration</li><li>Natural lighting and biophilic design elements</li><li>Flexible workspaces that adapt to different work styles</li><li>Technology integration for seamless connectivity</li><li>Comfortable breakout areas for informal meetings</li></ul><p>Research shows that well-designed office spaces can increase productivity by up to 20% and improve employee satisfaction significantly.</p>',
            'excerpt' => 'Discover how modern office design principles can transform your workspace into a productive and inspiring environment that boosts employee morale and efficiency.',
            'label' => 'Design',
            'link_text' => 'Read More',
            'meta_keywords' => 'office design, modern workspace, productivity, collaboration, workplace design',
            'meta_description' => 'Learn about modern office design principles and how they can enhance productivity and employee satisfaction in your workspace.'
        ],
        [
            'post_id' => 2,
            'language_id' => 1,
            'name' => 'Sustainable Construction: Building for the Future',
            'slug' => 'sustainable-construction-building-for-the-future',
            'content' => '<p>Sustainable construction is no longer just a trend—it\'s a necessity for the future of our planet. This comprehensive guide explores the key principles and practices that make construction projects environmentally responsible.</p><p>Essential sustainable construction practices include:</p><ul><li>Using eco-friendly building materials</li><li>Implementing energy-efficient systems</li><li>Reducing construction waste</li><li>Incorporating renewable energy sources</li><li>Designing for long-term sustainability</li></ul><p>Green building certifications like LEED and BREEAM provide frameworks for sustainable construction that benefit both the environment and building occupants.</p>',
            'excerpt' => 'Explore sustainable construction practices that reduce environmental impact while creating healthier, more efficient buildings for future generations.',
            'label' => 'Sustainability',
            'link_text' => 'Read More',
            'meta_keywords' => 'sustainable construction, green building, eco-friendly, LEED, environmental impact',
            'meta_description' => 'A comprehensive guide to sustainable construction practices and their positive impact on the environment and building performance.'
        ],
        [
            'post_id' => 3,
            'language_id' => 1,
            'name' => 'Kitchen Renovation: A Complete Planning Guide',
            'slug' => 'kitchen-renovation-complete-planning-guide',
            'content' => '<p>Kitchen renovation is one of the most rewarding home improvement projects, but it requires careful planning and execution. This guide walks you through every step of the process.</p><p>Key planning considerations include:</p><ul><li>Setting a realistic budget and timeline</li><li>Choosing the right layout and flow</li><li>Selecting durable, functional materials</li><li>Planning for adequate storage solutions</li><li>Incorporating modern appliances and technology</li></ul><p>Remember that the kitchen is the heart of the home, so design decisions should balance aesthetics with functionality and durability.</p>',
            'excerpt' => 'Master the art of kitchen renovation with our comprehensive planning guide that covers everything from budgeting to material selection.',
            'label' => 'Renovation',
            'link_text' => 'Read More',
            'meta_keywords' => 'kitchen renovation, home improvement, kitchen design, remodeling, planning guide',
            'meta_description' => 'Essential tips and comprehensive planning guide for successful kitchen renovation projects.'
        ],
        [
            'post_id' => 4,
            'language_id' => 1,
            'name' => 'Bathroom Design Trends for 2024',
            'slug' => 'bathroom-design-trends-2024',
            'content' => '<p>Bathroom design continues to evolve with new trends that prioritize both style and functionality. 2024 brings exciting innovations in bathroom design that transform this essential space.</p><p>Top bathroom design trends include:</p><ul><li>Minimalist designs with clean lines</li><li>Natural materials like stone and wood</li><li>Smart technology integration</li><li>Luxury spa-like features</li><li>Accessible design for all ages</li></ul><p>Modern bathrooms are becoming personal sanctuaries that offer both relaxation and functionality, with technology playing an increasingly important role.</p>',
            'excerpt' => 'Discover the latest bathroom design trends for 2024 that combine luxury, functionality, and innovative technology.',
            'label' => 'Design Trends',
            'link_text' => 'Read More',
            'meta_keywords' => 'bathroom design, design trends, 2024 trends, luxury bathroom, smart bathroom',
            'meta_description' => 'Explore the latest bathroom design trends for 2024, featuring luxury features and smart technology integration.'
        ],
        [
            'post_id' => 5,
            'language_id' => 1,
            'name' => 'Lighting Design: Illuminating Your Space',
            'slug' => 'lighting-design-illuminating-your-space',
            'content' => '<p>Lighting design is a crucial element that can make or break any interior space. Understanding lighting principles helps create environments that are both beautiful and functional.</p><p>Essential lighting design principles include:</p><ul><li>Layered lighting (ambient, task, and accent)</li><li>Color temperature and its psychological effects</li><li>Energy efficiency and LED technology</li><li>Natural light integration</li><li>Smart lighting controls</li></ul><p>Proper lighting design enhances mood, improves functionality, and can even affect our circadian rhythms and overall well-being.</p>',
            'excerpt' => 'Master the art of lighting design to create beautiful, functional spaces that enhance mood and improve daily living.',
            'label' => 'Lighting',
            'link_text' => 'Read More',
            'meta_keywords' => 'lighting design, interior lighting, LED lighting, smart lighting, lighting principles',
            'meta_description' => 'Complete guide to lighting design principles for creating beautiful and functional residential and commercial spaces.'
        ],
        [
            'post_id' => 6,
            'language_id' => 1,
            'name' => 'Flooring Options: Making the Right Choice',
            'slug' => 'flooring-options-making-the-right-choice',
            'content' => '<p>Choosing the right flooring material is one of the most important decisions in any construction or renovation project. Each option offers unique benefits and considerations.</p><p>Popular flooring options include:</p><ul><li>Hardwood: Classic beauty with durability</li><li>Laminate: Cost-effective and versatile</li><li>Tile: Water-resistant and low maintenance</li><li>Carpet: Comfort and sound absorption</li><li>Vinyl: Waterproof and budget-friendly</li></ul><p>Consider factors like lifestyle, budget, maintenance requirements, and room function when selecting flooring materials.</p>',
            'excerpt' => 'Compare different flooring options to find the perfect material that balances style, durability, and budget for your space.',
            'label' => 'Flooring',
            'link_text' => 'Read More',
            'meta_keywords' => 'flooring options, hardwood, laminate, tile, carpet, vinyl flooring',
            'meta_description' => 'Comprehensive comparison of flooring options to help you choose the perfect material for your space and lifestyle.'
        ],
        [
            'post_id' => 7,
            'language_id' => 1,
            'name' => 'Smart Home Technology in Modern Construction',
            'slug' => 'smart-home-technology-modern-construction',
            'content' => '<p>Smart home technology is revolutionizing how we live and interact with our living spaces. Modern construction projects increasingly incorporate these technologies for enhanced comfort and efficiency.</p><p>Key smart home features include:</p><ul><li>Automated lighting and climate control</li><li>Security and surveillance systems</li><li>Voice-controlled assistants</li><li>Energy monitoring and management</li><li>Integrated entertainment systems</li></ul><p>Smart home technology not only improves convenience but can also increase property value and reduce energy costs over time.</p>',
            'excerpt' => 'Explore how smart home technology is transforming modern construction and creating more connected, efficient living spaces.',
            'label' => 'Technology',
            'link_text' => 'Read More',
            'meta_keywords' => 'smart home, home automation, smart technology, IoT, connected home',
            'meta_description' => 'Discover how smart home technology integration is transforming modern construction and renovation projects.'
        ],
        [
            'post_id' => 8,
            'language_id' => 1,
            'name' => 'Outdoor Living Spaces: Extending Your Home',
            'slug' => 'outdoor-living-spaces-extending-your-home',
            'content' => '<p>Outdoor living spaces have become an essential extension of modern homes, providing additional areas for relaxation, entertainment, and connection with nature.</p><p>Popular outdoor living features include:</p><ul><li>Outdoor kitchens and dining areas</li><li>Comfortable seating and lounging spaces</li><li>Fire pits and outdoor fireplaces</li><li>Water features and pools</li><li>Landscaping and garden design</li></ul><p>Well-designed outdoor spaces can significantly increase your home\'s usable square footage and create valuable areas for family gatherings and entertainment.</p>',
            'excerpt' => 'Transform your outdoor area into a beautiful and functional living space that extends your home\'s comfort and entertainment options.',
            'label' => 'Outdoor Design',
            'link_text' => 'Read More',
            'meta_keywords' => 'outdoor living, patio design, outdoor kitchen, landscaping, garden design',
            'meta_description' => 'Design beautiful and functional outdoor living spaces that extend your home\'s living area and entertainment options.'
        ],
        [
            'post_id' => 9,
            'language_id' => 1,
            'name' => 'Color Psychology in Interior Design',
            'slug' => 'color-psychology-interior-design',
            'content' => '<p>Color psychology plays a crucial role in interior design, influencing our emotions, behavior, and perception of space. Understanding color theory helps create environments that support desired moods and functions.</p><p>Color psychology principles include:</p><ul><li>Warm colors (red, orange, yellow) for energy and excitement</li><li>Cool colors (blue, green, purple) for calm and relaxation</li><li>Neutral colors for balance and sophistication</li><li>Color intensity and its psychological impact</li><li>Cultural and personal color associations</li></ul><p>Strategic use of color can transform any space and significantly impact the well-being of its occupants.</p>',
            'excerpt' => 'Understand how color psychology influences interior design decisions and learn to create spaces that support desired moods and functions.',
            'label' => 'Color Theory',
            'link_text' => 'Read More',
            'meta_keywords' => 'color psychology, interior design, color theory, mood, emotional impact',
            'meta_description' => 'Learn how color psychology influences interior design and how to use color to create desired moods and atmospheres.'
        ],
        [
            'post_id' => 10,
            'language_id' => 1,
            'name' => 'Project Management in Construction',
            'slug' => 'project-management-construction',
            'content' => '<p>Effective project management is essential for successful construction and renovation projects. Proper planning, communication, and oversight ensure projects are completed on time, within budget, and to quality standards.</p><p>Key project management principles include:</p><ul><li>Comprehensive planning and scheduling</li><li>Clear communication and stakeholder management</li><li>Quality control and safety protocols</li><li>Budget management and cost control</li><li>Risk assessment and mitigation strategies</li></ul><p>Successful project management requires a balance of technical knowledge, leadership skills, and attention to detail.</p>',
            'excerpt' => 'Master the essential principles of project management for construction and renovation projects to ensure successful outcomes.',
            'label' => 'Project Management',
            'link_text' => 'Read More',
            'meta_keywords' => 'project management, construction management, planning, scheduling, quality control',
            'meta_description' => 'Essential project management principles and best practices for successful construction and renovation projects.'
        ]
    ];

    public function __construct()
    {
        $this->kernel = new KernelCli();
        $this->container = $this->kernel->getContainer();
        $this->repository = $this->container->make(PostRepositoryInterface::class);
    }

    public function seed(): void
    {
        $this->repository->insertPosts(['posts' => $this->posts, 'postContents' => $this->postContents]);
    }
    

} 