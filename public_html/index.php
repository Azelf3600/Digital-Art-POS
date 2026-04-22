<?php require_once 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starflow: Starlooms Digital Art Shop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <!-- HERO -->
    <section class="hero">
        <div class="hero__bg">
            <div class="hero__orb hero__orb--1"></div>
            <div class="hero__orb hero__orb--2"></div>
            <div class="hero__orb hero__orb--3"></div>
            <div class="hero__grid"></div>
        </div>

        <div class="hero__content">
            <div class="hero__label">
                <span class="dot"></span>
                Commission Studio Open
            </div>
            <h1 class="hero__title">
                <span class="hero__title--thin">Where art</span><br>
                <span class="hero__title--bold">meets vision.</span>
            </h1>
            <p class="hero__desc">
                Original digital artwork crafted to order. From portraits to full illustrations —
                every piece made with intention.
            </p>
            <div class="hero__actions">
                <a href="gallery.php" class="btn btn--primary">Browse Gallery</a>
                <a href="commission.php" class="btn btn--ghost">Start a Commission</a>
            </div>
            <div class="hero__stats">
                <div class="stat">
                    <span class="stat__num">150+</span>
                    <span class="stat__label">Artworks Sold</span>
                </div>
                <div class="stat__divider"></div>
                <div class="stat">
                    <span class="stat__num">98%</span>
                    <span class="stat__label">Happy Clients</span>
                </div>
                <div class="stat__divider"></div>
                <div class="stat">
                    <span class="stat__num">7–14</span>
                    <span class="stat__label">Days Turnaround</span>
                </div>
            </div>
        </div>

        <div class="hero__visual">
            <div class="hero__frame">
                <div class="hero__frame-inner">
                    <!-- Hero uses the image from assets/images/ui/ -->
                    <img
                        src="assets/images/ui/Shadow cat enhanced.png"
                        alt="Featured Artwork"
                        class="hero__img"
                    >
                </div>
                <div class="hero__frame-tag">
                    <span>Featured Work</span>
                    <span class="tag-price">₱2,500</span>
                </div>
            </div>
            <div class="hero__floating-card">
                <div class="fc__dot"></div>
                <div>
                    <div class="fc__title">New commission opened</div>
                    <div class="fc__sub">Character portrait · 2h ago</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURED WORKS -->
    <section class="section featured">
        <div class="container">
            <div class="section__header">
                <div class="section__label">Portfolio</div>
                <h2 class="section__title">Featured Works</h2>
                <a href="gallery.php" class="section__link">View All →</a>
            </div>

            <?php
            // 6 artworks — thumbnails folder for the grid cards
            $artworks = [
                [
                    'file'  => 'CrrptManipVsDax.png',
                    'title' => 'CrrptManip Vs Dax',
                    'cat'   => 'Character Art',
                    'price' => '₱2,000',
                ],
                [
                    'file'  => 'DinoHeadShot.png',
                    'title' => 'Dino Headshot',
                    'cat'   => 'Portrait',
                    'price' => '₱1,500',
                ],
                [
                    'file'  => 'Fusion vs Tinian.png',
                    'title' => 'Fusion vs Tinian',
                    'cat'   => 'Character Art',
                    'price' => '₱2,200',
                ],
                [
                    'file'  => 'JouseHaavok.png',
                    'title' => 'Jouse Haavok',
                    'cat'   => 'Portrait',
                    'price' => '₱1,800',
                ],
                [
                    'file'  => 'Shadow cat enhanced.png',
                    'title' => 'Shadow Cat',
                    'cat'   => 'Fan Art',
                    'price' => '₱2,500',
                ],
                [
                    'file'  => 'YzalHeadShot.png',
                    'title' => 'Yzal Headshot',
                    'cat'   => 'Portrait',
                    'price' => '₱1,500',
                ],
            ];
            ?>

            <div class="gallery-grid">
                <?php foreach ($artworks as $i => $art): ?>
                <div class="gallery-card" style="--delay: <?= ($i + 1) * 0.1 ?>s">
                    <div class="gallery-card__img">
                        <img
                            src="assets/artworks/thumbnails/<?= htmlspecialchars($art['file']) ?>"
                            alt="<?= htmlspecialchars($art['title']) ?>"
                            class="gallery-card__photo"
                            loading="lazy"
                        >
                        <div class="gallery-card__overlay">
                            <a href="artwork.php?id=<?= $i + 1 ?>" class="gallery-card__btn">View Work</a>
                        </div>
                    </div>
                    <div class="gallery-card__info">
                        <span class="gallery-card__cat"><?= htmlspecialchars($art['cat']) ?></span>
                        <span class="gallery-card__price"><?= $art['price'] ?></span>
                    </div>
                    <div class="gallery-card__title"><?= htmlspecialchars($art['title']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="section process">
        <div class="container">
            <div class="section__header section__header--center">
                <div class="section__label">Process</div>
                <h2 class="section__title">How It Works</h2>
            </div>
            <div class="process__steps">
                <div class="process__step">
                    <div class="step__num">01</div>
                    <div class="step__icon">◎</div>
                    <h3 class="step__title">Submit Brief</h3>
                    <p class="step__desc">Fill out the commission form with your vision, references, and preferred style.</p>
                </div>
                <div class="process__arrow">→</div>
                <div class="process__step">
                    <div class="step__num">02</div>
                    <div class="step__icon">◈</div>
                    <h3 class="step__title">Get a Quote</h3>
                    <p class="step__desc">Receive a detailed quote and timeline within 24 hours of your submission.</p>
                </div>
                <div class="process__arrow">→</div>
                <div class="process__step">
                    <div class="step__num">03</div>
                    <div class="step__icon">◐</div>
                    <h3 class="step__title">Review Drafts</h3>
                    <p class="step__desc">Follow progress with sketch and color previews. Two revision rounds included.</p>
                </div>
                <div class="process__arrow">→</div>
                <div class="process__step">
                    <div class="step__num">04</div>
                    <div class="step__icon">◉</div>
                    <h3 class="step__title">Receive Art</h3>
                    <p class="step__desc">Get your high-resolution final file once payment is complete.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING -->
    <section class="section pricing">
        <div class="container">
            <div class="section__header section__header--center">
                <div class="section__label">Pricing</div>
                <h2 class="section__title">Commission Tiers</h2>
            </div>
            <div class="pricing__grid">
                <div class="pricing__card">
                    <div class="pricing__tier">Sketch</div>
                    <div class="pricing__price">$10 <span>/ piece</span></div>
                    <ul class="pricing__list">
                        <li>Pencil or lineart style</li>
                        <li>Single character</li>
                        <li>3 revision round</li>
                        <li>PNG delivery</li>
                    </ul>
                    <a href="commission.php" class="btn btn--outline">Order Now</a>
                </div>
                <div class="pricing__card pricing__card--featured">
                    <div class="pricing__badge">Most Popular</div>
                    <div class="pricing__tier">Full Color</div>
                    <div class="pricing__price">$25 <span>/ piece</span></div>
                    <ul class="pricing__list">
                        <li>Full color + shading</li>
                        <li>Up to 2 characters</li>
                        <li>5 revision rounds</li>
                        <li>PNG + PSD delivery</li>
                    </ul>
                    <a href="commission.php" class="btn btn--primary">Order Now</a>
                </div>
                <div class="pricing__card">
                    <div class="pricing__tier">Illustrated</div>
                    <div class="pricing__price">$40 <span>/ piece</span></div>
                    <ul class="pricing__list">
                        <li>Full scene + background</li>
                        <li>Multiple characters</li>
                        <li>10 revision rounds</li>
                        <li>All source files</li>
                    </ul>
                    <a href="commission.php" class="btn btn--outline">Order Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- TESTIMONIALS -->
    <section class="section testimonials">
        <div class="container">
            <div class="section__header section__header--center">
                <div class="section__label">Reviews</div>
                <h2 class="section__title">What Clients Say</h2>
            </div>
            <div class="testimonials__grid">
                <div class="testimonial">
                    <div class="testimonial__stars">★★★★★</div>
                    <p class="testimonial__text">"Absolutely stunning work. The artist captured exactly what I imagined — even things I didn't know how to describe."</p>
                    <div class="testimonial__author">
                        <div class="testimonial__avatar">A</div>
                        <div>
                            <div class="testimonial__name">Andrea M.</div>
                            <div class="testimonial__type">Character Portrait</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial__stars">★★★★★</div>
                    <p class="testimonial__text">"Fast turnaround, excellent communication throughout. The final piece exceeded all my expectations."</p>
                    <div class="testimonial__author">
                        <div class="testimonial__avatar">R</div>
                        <div>
                            <div class="testimonial__name">Raffy T.</div>
                            <div class="testimonial__type">Full Illustration</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial__stars">★★★★★</div>
                    <p class="testimonial__text">"Used this for my book cover and couldn't be happier. Professional, creative, and genuinely talented."</p>
                    <div class="testimonial__author">
                        <div class="testimonial__avatar">S</div>
                        <div>
                            <div class="testimonial__name">Sofia L.</div>
                            <div class="testimonial__type">Book Cover</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="cta__bg">
            <div class="cta__orb"></div>
        </div>
        <div class="container">
            <div class="cta__content">
                <div class="section__label" style="justify-content:center">Ready?</div>
                <h2 class="cta__title">Bring your vision to life.</h2>
                <p class="cta__desc">Commissions are currently open. Limited slots available per month.</p>
                <div class="cta__actions">
                    <a href="commission.php" class="btn btn--primary btn--lg">Start a Commission</a>
                    <a href="contact.php" class="btn btn--ghost btn--lg">Get in Touch</a>
                </div>
            </div>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>