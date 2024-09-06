<?php
// The $title and $content variables are passed from the controller
?>

<h1><?= $title ?></h1>

<p>Welcome to our services page. We offer a wide range of professional services to meet your needs:</p>

<ul>
    <?php foreach ($content as $service): ?>
        <li><?= htmlspecialchars($service) ?></li>
    <?php endforeach; ?>
</ul>

<p>Contact us to learn more about how we can help you with these services.</p>

<!-- Additional content specific to the services page can be added here if needed -->