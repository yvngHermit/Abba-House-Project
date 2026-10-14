<?php
include '../includes/db.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO contacts (full_name, phone, email, subject, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['email'], $_POST['subject'], $_POST['message']]);
    $message = 'Your contact inquiry has been submitted.';
}
include '../includes/header.php';
?>
<header class="brown-header"><a href="home.php">‹</a><strong>Contact Page</strong></header>
<main class="page contact-page">
    <small>CONTACT US</small><h1>Get in Touch</h1>
    <div class="horizontal-scroll contact-scroll">
        <div class="contact-card">
            <img class="white-icon-img" src="../assets/images/Address_icon.png" alt="">
            <h3>Our Location</h3>
            <p>36 Freeland Ave<br>Kilner Park<br>Pretoria<br>0186</p>
            <a class="map-btn" target="_blank" href="https://www.google.com/maps/search/?api=1&query=36%20Freeland%20Ave%20Kilner%20Park%20Pretoria%200186">View on Maps</a>
        </div>
        <div class="contact-card">
            <img class="white-icon-img" src="../assets/images/Call_icon.png" alt="">
            <h3>Phone</h3>
            <p>+27 060 997 5257</p>
        </div>
    </div>
    <?php if ($message): ?><p class="success"><?= $message ?></p><?php endif; ?>
    <form method="post" class="contact-form">
        <label for="full_name">Full Name</label><input id="full_name" name="full_name" placeholder="Your name" required>
        <label for="phone">phone (optional)</label><input id="phone" name="phone" placeholder="+27:">
        <label for="email">Email:</label><input id="email" type="email" name="email" placeholder="email@gmail.com" required>
        <label for="subject">Subject</label><input id="subject" name="subject" placeholder="How can we help?" required>
        <label for="message">Message</label><textarea id="message" name="message" placeholder="tell us how you’d like to help or ask any questions here" required></textarea>
        <button class="black-btn">Submit</button>
    </form>
</main>
<?php include '../includes/footer.php'; ?>
