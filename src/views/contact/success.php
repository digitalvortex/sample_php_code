<div class="success-message">
  <h1><?= htmlspecialchars($title ?? 'Message Sent') ?></h1>
  <p><?= htmlspecialchars($message ?? 'Thank you for your message') ?></p>
  <a href="/contact" class="button">Back to Contact</a>
</div>