<div class="error-message">
  <h1><?= htmlspecialchars($title ?? 'Security Error') ?></h1>
  <p><?= htmlspecialchars($message ?? 'An error occurred') ?></p>
  <a href="/contact" class="button">Back to Contact Form</a>
</div>