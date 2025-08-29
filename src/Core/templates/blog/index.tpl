import(components/header.tpl, [data-v-component-header])


#whats-happening | before = <?php 
    if(isset($current_component)) $previous_component = $current_component;
	$whats_happening = $current_component = $this->_component['whatishappening']?? [];

	// echo '<pre>';
	// print_r($whats_happening['items']);
	// echo '</pre>';

?>
[data-v-component-whatishappening] [data-v-whatishappening-*]|innerText = $whats_happening['@@__data-v-whatishappening-(*)__@@']

div[data-v-whatishappening-items] > div.right-item | deleteAllButFirst
div[data-v-whatishappening-items] | prepend = <?php if(isset($whats_happening['items'])){ foreach ($whats_happening['items'] as $item) { ?>
img[data-v-whatishappeningitem-image] | src = <?php echo isset($item['image']) ? $item['image'] : ''; ?>
p[data-v-whatishappeningitem-title] | innerHTML = <?php echo isset($item['title']) ? $item['title'] : ''; ?>
div[data-v-whatishappening-items] | append = <?php }} ?>


#blog-list | before = <?php 
    if(isset($current_component)) $previous_component = $current_component;
	$latest_news = $current_component = $this->_component['latestnews']?? [];

	// echo '<pre>';
	// print_r($latest_news['items']);
	// echo '</pre>';

?>
[data-v-component-latestnews] [data-v-latestnews-*]|innerText = $latest_news['@@__data-v-latestnews-(*)__@@']

[data-v-latestnews-items] > div.project-list-item | deleteAllButFirst
[data-v-latestnews-items] | prepend = <?php if(isset($latest_news['items'])){ foreach ($latest_news['items'] as $item) { ?>
	img[data-v-latestnewsitem-image] | src = <?php echo isset($item['image']) ? $item['image'] : ''; ?>
	h6[data-v-latestnewsitem-title] | innerHTML = <?php echo isset($item['title']) ? $item['title'] : ''; ?>
	a[data-v-latestnewsitem-link] | href = <?php echo isset($item['link']) ? $item['link'] : ''; ?>
[data-v-latestnews-items] | append = <?php }} ?>



import(components/footer.tpl, [data-v-component-footer])