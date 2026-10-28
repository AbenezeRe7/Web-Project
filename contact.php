<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['subscribe'])) {
    if (filter_var($_GET['subscribe'], FILTER_VALIDATE_EMAIL)) {
        flash('contact', 'Thanks for subscribing! You will receive our latest offers at ' . sanitize($_GET['subscribe']) . '.', 'success');
    } else {
        flash('contact', 'Please enter a valid email address to subscribe.', 'danger');
    }
    redirect('index.php');
}

$form = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$sent = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $v) {
        $form[$k] = sanitize($_POST[$k] ?? '');
    }
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    }
    if ($form['name'] === '' || $form['message'] === '') {
        $errors[] = 'Name and message are required.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!$errors) {
        flash('contact', 'Thank you, ' . $form['name'] . '! Your message has been received. Our team will reply within 24 hours.', 'success');
        redirect('contact.php');
    }
}
if (is_logged_in() && $form['name'] === '') {
    $u = current_user();
    $form['name'] = $u['name'];
    $form['email'] = $u['email'];
}

$page_title = 'Contact Us';
require __DIR__ . '/includes/header.php';
?>

<div class="page-banner">
    <div class="container">
        <h1><i class="fa-solid fa-headset me-2"></i>Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="content-card h-100">
                <h5 class="mb-3">Get in touch</h5>
                <p class="text-muted">Questions about a product, an order or a return? Our support team is happy to help.</p>
                <ul class="contact-list">
                    <li><i class="fa-solid fa-location-dot"></i><div><strong>Address</strong><br>42 Tech Park Road, Bengaluru, Karnataka 560001</div></li>
                    <li><i class="fa-solid fa-phone"></i><div><strong>Phone</strong><br>+91 98765 43210</div></li>
                    <li><i class="fa-solid fa-envelope"></i><div><strong>Email</strong><br>support@electromart.com</div></li>
                    <li><i class="fa-solid fa-clock"></i><div><strong>Hours</strong><br>Mon - Sat: 9:00 AM - 8:00 PM</div></li>
                </ul>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="content-card">
                <h5 class="mb-3">Send us a message</h5>
                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="post" action="<?= url('contact.php') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="name">Name</label><input type="text" class="form-control" id="name" name="name" value="<?= e($form['name']) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" value="<?= e($form['email']) ?>" required></div>
                        <div class="col-12"><label class="form-label" for="subject">Subject</label><input type="text" class="form-control" id="subject" name="subject" value="<?= e($form['subject']) ?>"></div>
                        <div class="col-12"><label class="form-label" for="message">Message</label><textarea class="form-control" id="message" name="message" rows="5" required><?= e($form['message']) ?></textarea></div>
                    </div>
                    <button type="submit" class="btn btn-accent mt-3"><i class="fa-solid fa-paper-plane me-1"></i>Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
