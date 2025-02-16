<h1>Contact Us</h1>
<p>This is the contact page content.</p>
<form method="post" action="/contact/submit">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
  
  <div class="form-group">
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
    <?php if (isset($errors['name'])): ?>
      <span class="error"><?= htmlspecialchars($errors['name']) ?></span>
    <?php endif; ?>
  </div>
  
  <div class="form-group">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
    <?php if (isset($errors['email'])): ?>
      <span class="error"><?= htmlspecialchars($errors['email']) ?></span>
    <?php endif; ?>
  </div>
  
  <div class="form-group">
    <label for="message">Message:</label>
    <textarea id="message" name="message" required><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
    <?php if (isset($errors['message'])): ?>
      <span class="error"><?= htmlspecialchars($errors['message']) ?></span>
    <?php endif; ?>
  </div>
  
  <button type="submit">Send</button>
</form>