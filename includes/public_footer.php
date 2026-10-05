<?php
// Fetch business settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Graceful fallback if settings table doesn't exist yet
    // Do not crash the entire client website
}
?>
    <!-- Premium Footer -->
    <footer class="lux-footer">
        <div class="container">
            <div class="row gy-5">
                <div class="col-lg-4 pe-lg-5">
                    <span class="lux-footer__brand"><?= htmlspecialchars($settings['company_name'] ?? 'Patel Travels') ?></span>
                    <p style="line-height: 1.9; margin-top: 1rem;"><?= htmlspecialchars($settings['footer_description'] ?? 'Curated travel experiences.') ?></p>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <h5>Explore</h5>
                    <ul>
                        <li><a href="<?= url('destinations.php') ?>">Destinations</a></li>
                        <li><a href="<?= url('tours.php') ?>">Journeys</a></li>
                        <li><a href="<?= url('about.php') ?>">About Us</a></li>
                        <li><a href="<?= url('contact.php') ?>">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 col-6">
                    <h5>Company</h5>
                    <ul>
                        <li><a href="<?= url('about.php') ?>">Our Story</a></li>
                        <li><a href="<?= url('contact.php') ?>">Get In Touch</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4">
                    <h5>Contact</h5>
                    <ul style="line-height: 2;">
                        <li><span style="color: rgba(255,255,255,0.35);">Address</span><br><?= nl2br(htmlspecialchars($settings['address'] ?? '')) ?></li>
                        <li style="margin-top: 0.8rem;"><span style="color: rgba(255,255,255,0.35);">Email</span><br><a href="mailto:<?= htmlspecialchars($settings['email'] ?? '') ?>"><?= htmlspecialchars($settings['email'] ?? '') ?></a></li>
                        <li style="margin-top: 0.8rem;"><span style="color: rgba(255,255,255,0.35);">Phone</span><br><a href="tel:<?= htmlspecialchars($settings['phone'] ?? '') ?>"><?= htmlspecialchars($settings['phone'] ?? '') ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="lux-footer__bottom">
                <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['company_name'] ?? 'Patel Travels') ?>. All rights reserved.</span>
                <span><?= htmlspecialchars($settings['tagline'] ?? 'Curated Travel Experiences') ?></span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Navbar Scroll + Reveal Animations -->
    <script>
    (function() {
        // Navbar scroll behavior
        const nav = document.getElementById('luxNavbar');
        if (nav) {
            const threshold = 80;
            let ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        nav.classList.toggle('scrolled', window.scrollY > threshold);
                        ticking = false;
                    });
                    ticking = true;
                }
            });
            // Set initial state
            if (window.scrollY > threshold) nav.classList.add('scrolled');
        }

        // Reveal on scroll
        const reveals = document.querySelectorAll('.reveal');
        if (reveals.length) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            reveals.forEach(function(el) { observer.observe(el); });
        }

        // Lightbox
        document.addEventListener('click', function(e) {
            const galleryItem = e.target.closest('.lux-gallery__item');
            if (!galleryItem) return;
            e.preventDefault();
            const lb = document.getElementById('luxLightbox');
            if (!lb) return;
            const img = lb.querySelector('img');
            const src = galleryItem.querySelector('img')?.src;
            if (src) {
                img.src = src;
                lb.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
        document.addEventListener('click', function(e) {
            if (e.target.closest('.lux-lightbox__close') || (e.target.classList.contains('lux-lightbox') && e.target.classList.contains('active'))) {
                const lb = document.getElementById('luxLightbox');
                if (lb) {
                    lb.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const lb = document.getElementById('luxLightbox');
                if (lb && lb.classList.contains('active')) {
                    lb.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });
    })();
    </script>
</body>
</html>
