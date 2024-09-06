<h1><?= $title ?></h1>
<?= $content ?>
<form method="post" action="/contact/submit">
    <div>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <?php if (isset($errors['name'])): ?>
            <span class="error"><?= $errors['name'] ?></span>
        <?php endif; ?>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <?php if (isset($errors['email'])): ?>
            <span class="error"><?= $errors['email'] ?></span>
        <?php endif; ?>
    </div>
    <div>
        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea>
        <?php if (isset($errors['message'])): ?>
            <span class="error"><?= $errors['message'] ?></span>
        <?php endif; ?>
    </div>
    <button type="submit">Send</button>
</form>