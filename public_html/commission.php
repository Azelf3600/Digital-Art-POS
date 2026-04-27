<?php
// public_html/commission.php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

// Check if just submitted successfully
$submitted = isset($_GET['submitted']) && $_GET['submitted'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission — Starflow</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/commission.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Mono:wght@300;400;500&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <?php require_once 'includes/navbar.php'; ?>

    <!-- Success Overlay -->
    <div class="success-overlay" id="success-overlay" style="display:<?= $submitted ? 'flex' : 'none' ?>">
        <div class="success-overlay__card">
            <div class="success-overlay__icon">✦</div>
            <h2 class="success-overlay__title">Commission Submitted!</h2>
            <p class="success-overlay__desc">
                Your request has been received. We'll review your brief and
                get back to you with a quote within <strong>24 hours</strong>.
            </p>
            <p class="success-overlay__sub">
                You can track your commission status in your dashboard.
            </p>
            <div class="success-overlay__actions">
                <a href="dashboard.php" class="btn btn--primary">Go to Dashboard</a>
                <button class="btn btn--ghost" id="success-close">
                    Okay, Thank You
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="commission-header">
        <div class="commission-header__bg">
            <div class="commission-header__orb"></div>
        </div>
        <div class="container">
            <div class="section__label">Custom Work</div>
            <h1 class="commission-header__title">Request a Commission</h1>
            <p class="commission-header__desc">
                Tell us your vision and we'll bring it to life. Fill out the form below
                and we'll get back to you with a quote within 24 hours.
            </p>
        </div>
    </div>

    <div class="commission-page">
        <div class="container">
            <div class="commission-layout">

                <!-- Left: Form -->
                <div class="commission-form-col">

                    <?php show_flash(); ?>

                    <?php if (!is_logged_in()): ?>
                    <div class="commission-login-notice">
                        <p>You need to be logged in to submit a commission.</p>
                        <div class="commission-login-notice__actions">
                            <a href="login.php" class="btn btn--primary">Login</a>
                            <a href="register.php" class="btn btn--ghost">Create Account</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form
                        class="commission-form <?= !is_logged_in() ? 'commission-form--disabled' : '' ?>"
                        action="actions/commission-submit.php"
                        method="POST"
                        enctype="multipart/form-data"
                        id="commission-form"
                        <?= !is_logged_in() ? 'onsubmit="return false"' : '' ?>
                    >
                        <?= csrf_field() ?>

                        <!-- Title -->
                        <div class="form-group">
                            <label class="form-label" for="title">
                                Commission Title <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-input"
                                placeholder="e.g. Fantasy character portrait of my OC"
                                required
                                maxlength="150"
                            >
                        </div>

                        <!-- Tier -->
                        <div class="form-group">
                            <label class="form-label">
                                Commission Tier <span class="required">*</span>
                            </label>
                            <div class="tier-cards" id="tier-cards">
                                <label class="tier-card">
                                    <input type="radio" name="tier" value="sketch" required>
                                    <div class="tier-card__inner">
                                        <div class="tier-card__name">Sketch</div>
                                        <div class="tier-card__price">$10</div>
                                        <ul class="tier-card__list">
                                            <li>Pencil / lineart</li>
                                            <li>Single character</li>
                                            <li>1 revision</li>
                                        </ul>
                                    </div>
                                </label>
                                <label class="tier-card">
                                    <input type="radio" name="tier" value="full_color" required>
                                    <div class="tier-card__inner">
                                        <div class="tier-card__badge">Popular</div>
                                        <div class="tier-card__name">Full Color</div>
                                        <div class="tier-card__price">$25</div>
                                        <ul class="tier-card__list">
                                            <li>Full color + shading</li>
                                            <li>Up to 2 characters</li>
                                            <li>2 revisions</li>
                                        </ul>
                                    </div>
                                </label>
                                <label class="tier-card">
                                    <input type="radio" name="tier" value="illustrated" required>
                                    <div class="tier-card__inner">
                                        <div class="tier-card__name">Illustrated</div>
                                        <div class="tier-card__price">$40</div>
                                        <ul class="tier-card__list">
                                            <li>Full scene + background</li>
                                            <li>Multiple characters</li>
                                            <li>3 revisions</li>
                                        </ul>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Style — matches gallery dropdown style -->
                        <div class="form-group">
                            <label class="form-label" for="style">Preferred Style</label>
                            <select name="style" id="style" class="gallery-select" style="width:100%;">
                                <option value="">— Select a style —</option>
                                <option value="Anime / Manga">Anime / Manga</option>
                                <option value="Semi-Realistic">Semi-Realistic</option>
                                <option value="Chibi">Chibi</option>
                                <option value="Western Cartoon">Western Cartoon</option>
                                <option value="Painterly">Painterly</option>
                                <option value="Pixel Art">Pixel Art</option>
                                <option value="Artist's Choice">Artist's Choice</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label class="form-label" for="description">
                                Description / Brief <span class="required">*</span>
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-input form-textarea"
                                placeholder="Describe your character, scene, mood, colors, pose, expressions, etc. The more detail, the better!"
                                rows="6"
                                required
                                maxlength="2000"
                            ></textarea>
                            <div class="form-char-count">
                                <span id="desc-count">0</span> / 2000
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label class="form-label" for="customer_notes">
                                Additional Notes
                            </label>
                            <textarea
                                id="customer_notes"
                                name="customer_notes"
                                class="form-input form-textarea"
                                placeholder="Deadlines, budget concerns, anything else we should know..."
                                rows="3"
                                maxlength="1000"
                            ></textarea>
                        </div>

                        <!-- Reference Images -->
                        <div class="form-group">
                            <label class="form-label">Reference Images</label>
                            <div class="file-upload" id="file-upload">
                                <input
                                    type="file"
                                    name="references[]"
                                    id="references"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    class="file-upload__input"
                                >
                                <div class="file-upload__area" id="upload-area">
                                    <div class="file-upload__icon">⊕</div>
                                    <p class="file-upload__text">Click or drag images here</p>
                                    <p class="file-upload__hint">
                                        JPG, PNG, WebP — Max 5MB each — Up to 5 files
                                    </p>
                                </div>
                                <div class="file-upload__preview" id="file-preview"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn--primary btn--full btn--lg">
                            Submit Commission Request
                        </button>

                    </form>
                </div>

                <!-- Right: Info Panel -->
                <div class="commission-info-col">

                    <div class="commission-info-card">
                        <h3 class="commission-info-card__title">How It Works</h3>
                        <div class="commission-steps">
                            <div class="commission-step">
                                <div class="commission-step__num">01</div>
                                <div>
                                    <div class="commission-step__title">Submit Brief</div>
                                    <div class="commission-step__desc">Fill out this form with your vision and references.</div>
                                </div>
                            </div>
                            <div class="commission-step">
                                <div class="commission-step__num">02</div>
                                <div>
                                    <div class="commission-step__title">Get a Quote</div>
                                    <div class="commission-step__desc">We'll review and send a quote within 24 hours.</div>
                                </div>
                            </div>
                            <div class="commission-step">
                                <div class="commission-step__num">03</div>
                                <div>
                                    <div class="commission-step__title">Review Drafts</div>
                                    <div class="commission-step__desc">See sketch previews and request revisions.</div>
                                </div>
                            </div>
                            <div class="commission-step">
                                <div class="commission-step__num">04</div>
                                <div>
                                    <div class="commission-step__title">Receive Art</div>
                                    <div class="commission-step__desc">Get your high-res file after final payment.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="commission-info-card">
                        <h3 class="commission-info-card__title">Pricing</h3>
                        <div class="commission-turnaround">
                            <div class="turnaround-row">
                                <span>Sketch</span>
                                <span>$10</span>
                            </div>
                            <div class="turnaround-row">
                                <span>Full Color</span>
                                <span>$25</span>
                            </div>
                            <div class="turnaround-row">
                                <span>Illustrated</span>
                                <span>$40</span>
                            </div>
                        </div>
                    </div>

                    <div class="commission-info-card">
                        <h3 class="commission-info-card__title">Turnaround Times</h3>
                        <div class="commission-turnaround">
                            <div class="turnaround-row">
                                <span>Sketch</span>
                                <span>3–5 days</span>
                            </div>
                            <div class="turnaround-row">
                                <span>Full Color</span>
                                <span>7–10 days</span>
                            </div>
                            <div class="turnaround-row">
                                <span>Illustrated</span>
                                <span>14–21 days</span>
                            </div>
                        </div>
                    </div>

                    <div class="commission-info-card commission-info-card--tos">
                        <h3 class="commission-info-card__title">Terms of Service</h3>
                        <ul class="commission-tos">
                            <li>50% deposit required before work begins</li>
                            <li>Final file delivered after full payment</li>
                            <li>Personal use only unless commercial license purchased</li>
                            <li>No refunds once sketch is approved</li>
                            <li>Artist retains right to post work in portfolio</li>
                        </ul>
                        <a href="terms.php" class="commission-tos__link">Read full terms →</a>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="assets/js/commission.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>