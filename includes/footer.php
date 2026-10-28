</main>

<footer class="site-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-lg-5 col-md-6">
                <a class="brand-logo footer-brand" href="<?= url('index.php') ?>">
                    <span class="brand-icon"><i class="fa-solid fa-bolt"></i></span> <span>Electro<span class="text-accent">Mart</span></span>
                </a>
                <p class="mt-3 footer-text">
                    ElectroMart is your one-stop destination for the latest smartphones, laptops, audio gear,
                    smart home devices, gaming consoles, cameras and accessories. 100% genuine products,
                    secure checkout and fast delivery across India.
                </p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= url('index.php') ?>"><i class="fa-solid fa-angle-right me-2"></i>Home</a></li>
                    <li><a href="<?= url('products.php') ?>"><i class="fa-solid fa-angle-right me-2"></i>Shop</a></li>
                    <li><a href="<?= url('cart.php') ?>"><i class="fa-solid fa-angle-right me-2"></i>Cart</a></li>
                    <li><a href="<?= url('orders.php') ?>"><i class="fa-solid fa-angle-right me-2"></i>My Orders</a></li>
                    <li><a href="<?= url('contact.php') ?>"><i class="fa-solid fa-angle-right me-2"></i>Contact</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-12">
                <h5 class="footer-title">Contact Us</h5>
                <ul class="footer-contact">
                    <li><i class="fa-solid fa-location-dot"></i> 42 Tech Park Road, Bengaluru, Karnataka 560001</li>
                    <li><i class="fa-solid fa-phone"></i> +91 98765 43210</li>
                    <li><i class="fa-solid fa-envelope"></i> support@electromart.com</li>
                    <li><i class="fa-solid fa-clock"></i> Mon - Sat: 9:00 AM - 8:00 PM</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <span>&copy; <?= date('Y') ?> ElectroMart. All rights reserved.</span>
            <span class="payment-icons">
                <i class="fa-brands fa-cc-visa"></i><i class="fa-brands fa-cc-mastercard"></i><i class="fa-brands fa-google-pay"></i><i class="fa-solid fa-money-bill-wave"></i>
            </span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
