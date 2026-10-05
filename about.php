<?php
/**
 * About Us — Patel Travels
 */
$pageTitle = 'Our Story';
$metaDescription = 'Learn about Patel Travels. We design curated journeys around the places worth remembering.';
require_once __DIR__ . '/includes/public_header.php';
require_once __DIR__ . '/includes/public_navbar.php';
?>

<section class="page-hero" style="height: 60vh; min-height: 500px;">
    <div class="page-hero__bg" style="background-image: url('<?= get_public_image(null, 'hero') ?>');"></div>
    <div class="page-hero__content">
        <p class="eyebrow mb-3">The Company</p>
        <h1 style="font-family: var(--font-heading); font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 400; color: #fff;">About Patel Travels</h1>
    </div>
</section>

<section class="section-pad">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 reveal">
                <p class="eyebrow mb-3">Our Story</p>
                <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 400; line-height: 1.15; color: var(--color-charcoal); margin-bottom: 1.5rem;">Journeys designed around<br>the places worth remembering.</h2>
                <div style="color: var(--color-muted); line-height: 1.8;">
                    <p class="mb-4">We believe travel is not simply about reaching a destination. It is about the experiences along the way — the cultures you encounter, the landscapes that take your breath away, and the moments that stay with you long after you return.</p>
                    <p>Every journey we craft is built on local insight, personal attention, and a genuine love for the art of travel. We take care of the details, so you can immerse yourself in the journey.</p>
                </div>
            </div>
            <div class="col-lg-6 offset-lg-1 reveal">
                <div class="row g-4">
                    <div class="col-6">
                        <img src="<?= get_public_image(null, 'destination') ?>" alt="Travel Experience" class="w-100 object-fit-cover" style="height: 400px;">
                    </div>
                    <div class="col-6 mt-5">
                        <img src="<?= get_public_image(null, 'plan') ?>" alt="Cultural Journey" class="w-100 object-fit-cover" style="height: 400px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-pad bg-stone">
    <div class="container">
        <div class="lux-section-head reveal">
            <p class="eyebrow mb-3">The Experience</p>
            <h2>Why travel with us</h2>
        </div>
        <div class="row g-5">
            <div class="col-md-4 reveal">
                <h4 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 500; color: var(--color-charcoal); margin-bottom: 1rem;">Thoughtfully Planned</h4>
                <p style="color: var(--color-muted); line-height: 1.8;">Every detail considered, from the first enquiry to the final day of your journey. We design itineraries that balance exploration with relaxation.</p>
            </div>
            <div class="col-md-4 reveal">
                <h4 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 500; color: var(--color-charcoal); margin-bottom: 1rem;">Local Insight</h4>
                <p style="color: var(--color-muted); line-height: 1.8;">Experience destinations through the eyes of those who know them best. We connect you with authentic local cultures and experiences.</p>
            </div>
            <div class="col-md-4 reveal">
                <h4 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 500; color: var(--color-charcoal); margin-bottom: 1rem;">Human Support</h4>
                <p style="color: var(--color-muted); line-height: 1.8;">Real people, available when you need them. Before, during and after your trip, our team is dedicated to ensuring a seamless experience.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-pad text-center">
    <div class="container reveal">
        <h2 style="font-family: var(--font-heading); font-size: clamp(2rem, 4vw, 3.5rem); font-weight: 400; color: var(--color-charcoal); margin-bottom: 1.5rem;">Ready to begin your journey?</h2>
        <a href="<?= url('contact.php') ?>" class="btn-lux mt-4">Speak to an Advisor</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
