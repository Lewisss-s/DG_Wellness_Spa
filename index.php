
<?php
/*require_once __DIR__ . "/config/database.php";

$pageTitle = "Home";
$currentPage = "home";

$featuredServices = [];
$servicesError = false;

try {
    $stmt = $pdo->query(
        "SELECT id, category, service_name, description, price, duration_minutes
         FROM services
         WHERE is_active = 1
         ORDER BY id DESC
         LIMIT 3"
    );

    $featuredServices = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Homepage services query failed: " . $e->getMessage());
    $servicesError = true;
}

require_once __DIR__ . "/includes/header.php";
?>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">YOUR WELLNESS, YOUR TIME</span>

        <h1>Make time for <span>yourself.</span></h1>

        <p>
            Step away from your busy day and enjoy a moment of relaxation.
            Explore our massage and beauty services at DG Wellness Spa.
        </p>

        <div class="hero-actions">
            <a href="/DG_Wellness_Spa/services.php" class="button button-primary">
                Explore Our Services
            </a>

            <a href="/DG_Wellness_Spa/register.php" class="button button-light">
                Get Started
            </a>
        </div>

        <div class="hero-note">
            <span class="hero-note-dot"></span>
            <span>Open daily from 12:00 PM to 9:00 PM</span>
        </div>
    </div>

    <div class="hero-visual" aria-label="Spa relaxation illustration">
        <div class="visual-circle visual-circle-large"></div>
        <div class="visual-circle visual-circle-small"></div>

        <div class="visual-card">
            <span class="visual-icon">✿</span>
            <p class="visual-label">A moment for you</p>
            <h2>Rest. Relax. Renew.</h2>
            <p class="visual-description">
                Make your well-being a priority.
            </p>
        </div>

        <div class="floating-note">
            <span class="floating-note-icon">♡</span>
            <span>Self-care starts here</span>
        </div>
    </div>
</section>

<section class="welcome-section" id="about">
    <div class="section-heading">
        <span class="eyebrow">WELCOME TO DG WELLNESS SPA</span>
        <h2>A little care goes a long way.</h2>
        <p>
            Discover services designed to give you time to relax, refresh,
            and care for yourself.
        </p>
    </div>

    <div class="benefit-grid">
        <article class="benefit-card">
            <span class="benefit-icon">✧</span>
            <h3>Time to Relax</h3>
            <p>Take a break from your daily routine and enjoy some personal time.</p>
        </article>

        <article class="benefit-card">
            <span class="benefit-icon">❀</span>
            <h3>Beauty and Wellness</h3>
            <p>Explore available massage and nail care services in one place.</p>
        </article>

        <article class="benefit-card">
            <span class="benefit-icon">♡</span>
            <h3>Convenient Booking</h3>
            <p>Register for an account to access the appointment booking system.</p>
        </article>
    </div>
</section>

<section class="featured-section" id="featured-services">
    <div class="section-heading section-heading-row">
        <div>
            <span class="eyebrow">EXPLORE OUR SERVICES</span>
            <h2>Find your moment of care.</h2>
            <p>Browse our available treatments and service options.</p>
        </div>

        <a class="text-link" href="/DG_Wellness_Spa/services.php">
            View All Services &rarr;
        </a>
    </div>

    <?php if ($servicesError): ?>
        <div class="notice notice-error">
            Services are temporarily unavailable. Please try again later.
        </div>

    <?php elseif (empty($featuredServices)): ?>
        <div class="empty-state">
            <span class="empty-state-icon">✿</span>
            <h3>Services are coming soon.</h3>
            <p>Our available services will appear here once added to the system.</p>
            <a class="button button-primary" href="/DG_Wellness_Spa/services.php">
                View Services
            </a>
        </div>

    <?php else: ?>
        <div class="service-grid">
            <?php foreach ($featuredServices as $service): ?>
                <article class="service-card">
                    <div class="service-card-top">
                        <span class="service-symbol">✿</span>
                        <span class="category-label">
                            <?= spaEscape($service["category"]) ?>
                        </span>
                    </div>

                    <h3><?= spaEscape($service["service_name"]) ?></h3>

                    <p class="service-description">
                        <?php
                        $description = trim($service["description"] ?? "");
                        echo spaEscape(
                            $description !== ""
                                ? $description
                                : "Discover this service at DG Wellness Spa."
                        );
                        ?>
                    </p>

                    <div class="service-meta">
                        <span>
                            <?= (int) $service["duration_minutes"] ?> minutes
                        </span>
                        <strong>
                            &#8369;<?= number_format((float) $service["price"], 2) ?>
                        </strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="center-action">
            <a class="button button-outline" href="/DG_Wellness_Spa/services.php">
                Browse All Services
            </a>
        </div>
    <?php endif; ?>
</section>

<section class="booking-banner">
    <div>
        <span class="eyebrow">YOUR NEXT STEP</span>
        <h2>Ready to plan your visit?</h2>
        <p>Create an account to access the spa's appointment booking system.</p>
    </div>

    <a class="button button-light" href="/DG_Wellness_Spa/register.php">
        Create an Account
    </a>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>*/


require_once __DIR__ . "/config/database.php";

$services = [];

try {
    $stmt = $pdo->query("
        SELECT category, service_name, description, price, duration_minutes
        FROM services
        WHERE is_active = 1
        ORDER BY category, service_name
        LIMIT 6
    ");

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $services = [];
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DG Wellness Spa | Relax, Refresh, Renew</title>
    <meta name="description" content="Discover DG Wellness Spa services and book your next self-care appointment.">
    <link rel="stylesheet" href="/DG_Wellness_Spa/assets/css/index.css">
</head>
<body class="spa-home">


<header class="home-header">
    <a class="spa-brand" href="/DG_Wellness_Spa/index.php">
        <img
            src="/DG_Wellness_Spa/assets/images/Brandlogo.png"
            alt="DG Wellness Spa Logo"
            class="brand-logo"
        >

        <span class="brand-text">
            <strong>DG Wellness Spa</strong>
            <small>Relax. Refresh. Renew.</small>
        </span>
    </a>

    <nav class="home-nav">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#services">Services</a>
        <a href="#contact">Contact</a>

        <a class="book-button"
           href="/DG_Wellness_Spa/book.php">
            Book Now
        </a>
    </nav>
</header>

<main>
    <section class="home-hero" id="home">
        <div class="hero-content">
            <p class="hero-eyebrow">
                YOUR TIME FOR SELF-CARE
            </p>

            <h1>
                Relax your body.<br>
                <span>Refresh your mind.</span>
            </h1>

            <p class="hero-description">
                Take a break from your busy day and give yourself
                the care you deserve. Discover a welcoming space
                for relaxation, beauty, and wellness at
                DG Wellness Spa.
            </p>

            <div class="hero-actions">
                <a class="book-button hero-book"
                   href="/DG_Wellness_Spa/book.php">
                    Book an Appointment
                </a>

                <a class="outline-button" href="#services">
                    Explore Our Services
                </a>
            </div>

            <p class="hero-note">
                Your wellness journey starts with a moment
                for yourself.
            </p>
        </div>
    </section>
</main>


    <section class="home-about section-padding" id="about">
        <div class="section-heading">
            <p class="section-eyebrow">WELCOME TO DG WELLNESS SPA</p>
            <h2>Your space to unwind</h2>
            <p>
                We offer wellness and self-care services designed to help
                you take a pause, feel refreshed, and enjoy time for yourself.
                Whether you are looking for relaxation or personal care,
                we welcome you to experience our services.
            </p>
        </div>

        <div class="about-highlights">
            <article class="highlight-card">
                <span class="highlight-icon">01</span>
                <h3>Relaxation</h3>
                <p>Take a break and enjoy a moment away from your daily routine.</p>
            </article>

            <article class="highlight-card">
                <span class="highlight-icon">02</span>
                <h3>Personal Care</h3>
                <p>Explore services that support your beauty and self-care routine.</p>
            </article>

            <article class="highlight-card">
                <span class="highlight-icon">03</span>
                <h3>Convenient Booking</h3>
                <p>Send an appointment request through our online booking system.</p>
            </article>
        </div>
    </section>

    <section class="home-services section-padding" id="services">
        <div class="section-heading">
            <p class="section-eyebrow">DISCOVER OUR SERVICES</p>
            <h2>Care for yourself</h2>
            <p>Explore some of the services available at our spa.</p>
        </div>

        <?php if (!empty($services)): ?>
            <div class="service-grid">
                <?php foreach ($services as $service): ?>
                    <article class="home-service-card">
                        <span class="service-category">
                            <?= e($service["category"]) ?>
                        </span>

                        <h3><?= e($service["service_name"]) ?></h3>

                        <p>
                            <?= e(
                                $service["description"]
                                ?: "Ask us about this service and find an appointment that works for you."
                            ) ?>
                        </p>

                        <div class="service-details">
                            <strong>
                                ₱<?= number_format((float) $service["price"], 2) ?>
                            </strong>
                            <span>
                                <?= (int) $service["duration_minutes"] ?> minutes
                            </span>
                        </div>

                        <a href="/DG_Wellness_Spa/book.php" class="service-book-link">
                            Book this service →
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-services">
                Our services are being updated. Please check back soon.
            </p>
        <?php endif; ?>

        <div class="center-action">
            <a href="/DG_Wellness_Spa/services.php" class="outline-button">
                View All Services
            </a>
        </div>
    </section>

    <section class="home-cta">
        <p class="section-eyebrow">YOUR NEXT MOMENT OF RELAXATION</p>
        <h2>Ready to make time for yourself?</h2>
        <p>Choose a service and send your appointment request online.</p>
        <a class="book-button hero-book" href="/DG_Wellness_Spa/book.php">
            Book Your Appointment
        </a>
    </section>

    <section class="home-contact section-padding" id="contact">
        <div>
            <p class="section-eyebrow">VISIT US</p>
            <h2>We look forward to welcoming you.</h2>
        </div>

        <div class="contact-details">
            <p><strong>DG Wellness Spa</strong></p>
            <p>
                G/F Stall 1, Pilar A Bldg., Ed. Bautista St. corner
                Malihan St., Brgy. Zone IV, Dasmariñas City, Cavite
            </p>
            <p><strong>Business hours:</strong> 12:00 PM – 9:00 PM</p>
            <a href="/DG_Wellness_Spa/book.php" class="service-book-link">
                Make an appointment →
            </a>
        </div>
    </section>
</main>

<footer class="home-footer">
    <p>&copy; <?= date("Y") ?> DG Wellness Spa. All rights reserved.</p>
    <p>Relax. Refresh. Renew.</p>
</footer>

</body>
</html>
