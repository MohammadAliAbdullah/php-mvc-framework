#app | prepend = <?php
    $data = $this->parameters;
?>

h1[data-v-title] | innerText = <?php echo $data['title']; ?>
h3[data-v-description] | innerText = <?php echo $data['description']; ?>
p[data-v-intro] | innerText = <?php echo $data['intro']; ?>

#instructions | prepend = <?php foreach($data['instructions'] as $instruction) { ?>
    li[data-v-instruction] | innerHTML = <?php echo $instruction; ?>
#instructions | append = <?php }?>

a[data-v-action] | innerText = <?php echo $data['button_text']; ?>
