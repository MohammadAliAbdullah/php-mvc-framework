import(components/header.tpl, [data-v-component-header])

#th-blog-main | before = <?php 
    if(isset($current_component)) $previous_component = $current_component;
	$blogmain = $current_component = $this->_component['blogmain']?? [];

	// echo '<pre>';
	// print_r($blogmain);
	// echo '</pre>';
?>
[data-v-component-blogmain] [data-v-blogmain-*]|innerText = $blogmain['@@__data-v-blogmain-(*)__@@']

h2[data-v-blogmain-section_title]|innerText = <?php echo isset($blogmain['section_title']) ? $blogmain['section_title'] : ''; ?>
div[data-v-blogmain-section_subtitle]|innerText = <?php echo isset($blogmain['section_subtitle']) ? $blogmain['section_subtitle'] : ''; ?>
img[data-v-blogmain-img] | src = <?php echo isset($blogmain['img']) ? $blogmain['img'] : ''; ?>
p[data-v-blogmain-section_subtitle2]|innerText = <?php echo isset($blogmain['section_subtitle2']) ? $blogmain['section_subtitle2'] : ''; ?>
p[data-v-blogmain-section_subtitle3]|innerText = <?php echo isset($blogmain['section_subtitle3']) ? $blogmain['section_subtitle3'] : ''; ?>
p[data-v-blogmain-section_subtitle4]|innerText = <?php echo isset($blogmain['section_subtitle4']) ? $blogmain['section_subtitle4'] : ''; ?>



#blog-detail-gallery| before = <?php 
    if(isset($current_component)) $previous_component = $current_component;
	$blog_gallery = $current_component = $this->_component['bloggallery']?? [];

	// echo '<pre>';
	// print_r($blog_gallery);
	// echo '</pre>';

?>
div[data-v-projectgallery-thumbs] > button | deleteAllButFirst
div[data-v-projectgallery-thumbs] | prepend = <?php if(isset($blog_gallery['items'])){ foreach ($blog_gallery['items'] as $item) { ?>
button[data-v-projectgalleryitem-thumb] | class = <?php echo isset($item["thumb_class"]) ? $item["thumb_class"] : ''; ?>
img[data-v-projectgalleryitem-thumb_image] | src = <?php echo isset($item["thumb_image"]) ? $item["thumb_image"] : ''; ?>
button[data-v-projectgalleryitem-thumb] | id = <?php echo isset($item["thumb_id"]) ? $item["thumb_id"] : ''; ?>
button[data-v-projectgalleryitem-thumb] | data-bs-target = <?php echo isset($item["target"]) ? $item["target"] : ''; ?>
div[data-v-projectgallery-thumbs] | append = <?php }} ?>

div[data-v-bloggallery-content] > div | deleteAllButFirst
div[data-v-bloggallery-content] | prepend = <?php if(isset($blog_gallery['items'])){ foreach ($blog_gallery['items'] as $tabItem) { ?>
div[data-v-bloggallery-content-item] | class = <?php echo isset($tabItem["class"]) ? $tabItem["class"] : ''; ?>
img[data-v-bloggallery-content-item-img] | src = <?php echo isset($tabItem["image"]) ? $tabItem["image"] : ''; ?>
div[data-v-bloggallery-content-item] | id = <?php echo isset($tabItem["id"]) ? $tabItem["id"] : ''; ?>
div[data-v-bloggallery-content-item] | aria-labelledby = <?php echo isset($tabItem["thumb_id"]) ? $tabItem["thumb_id"] : ''; ?>
div[data-v-bloggallery-content] | append = <?php }} ?>






import(components/featured_product_slider.tpl, [data-v-component-featuredproductslider])


#th-featured-material | before = <?php 
    if(isset($current_component)) $previous_component = $current_component;
	$featuredmaterialslider = $current_component = $this->_component['featuredmaterialslider']?? [];

	// echo '<pre>';
	// print_r($featuredmaterialslider);
	// echo '</pre>';
?>
[data-v-component-featuredmaterialslider] [data-v-featuredmaterialslider-*]|innerText = $featuredmaterialslider['@@__data-v-featuredmaterialslider-(*)__@@']

.th-featured-material-slider > .swiper-wrapper > div.swiper-slide | deleteAllButFirst
.th-featured-material-slider > .swiper-wrapper| prepend = <?php if(isset($featuredmaterialslider['items'])){ foreach ($featuredmaterialslider['items'] as $materialItem) { ?>
img[data-v-featuredmaterialslideritem-image] | src = <?php echo isset($materialItem["image"]) ? $materialItem["image"] : ''; ?>
p[data-v-featuredmaterialslideritem-category] | innerHTML = <?php echo isset($materialItem["category"]) ? $materialItem["category"] : ''; ?>
h3[data-v-featuredmaterialslideritem-name] | innerHTML = <?php echo isset($materialItem["name"]) ? $materialItem["name"] : ''; ?>
span[data-v-featuredmaterialslideritem-description] | innerHTML = <?php echo isset($materialItem["description"]) ? $materialItem["description"] : ''; ?>
.th-featured-material-slider > .swiper-wrapper | append = <?php }} ?>



import(components/footer.tpl, [data-v-component-footer])