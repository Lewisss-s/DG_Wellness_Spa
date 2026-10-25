
<?php
require_once __DIR__ . "/config/database.php";

$pageTitle = "Our Services";
$currentPage = "services";

$services = [];
$servicesByCategory = [];
$servicesError = false;

try {
    $stmt = $pdo->query(
        "SELECT id, category, service_name, description, price, duration_minutes
         FROM services
         WHERE is_active = 1
         ORDER BY category ASC, service_name ASC"
    );

    $services = $stmt->fetchAll();

    foreach ($services as $service) {
        $category = trim($service["category"] ?? "");

        if ($category === "") {
            $category = "Other Services";
        }

        $servicesByCategory[$category][] = $service;
    }
} catch (PDOException $e) {
    error_log("Services page query failed: " . $e->getMessage());
    $servicesError = true;
}

require_once __DIR__ . "/includes/header.php";
?>

<section class="page-banner">
    <span class="eyebrow">CARE FOR YOURSELF</span>
    <h1>Our Services</h1>
    <p>
        Explore our available treatments, compare prices, and find
        a service that fits your needs.
    </p>
</section>

<section class="services-page-section">
    <?php if ($servicesError): ?>
        <div class="notice notice-error">
            We couldn't load the service list. Please try again later.
        </div>

    <?php elseif (empty($servicesByCategory)): ?>
        <div class="empty-state">
            <span class="empty-state-icon">✿</span>
            <h2>No services available yet</h2>
            <p>
                Please check back later or contact DG Wellness Spa
                for information about available services.
            </p>
        </div>

    <?php else: ?>
        <div class="services-intro">
            <p>
                <?= count($services) ?> available
                <?= count($services) === 1 ? "service" : "services" ?>
            </p>
            <p>Prices and durations are based on the spa's service records.</p>
        </div>

        <?php foreach ($servicesByCategory as $category => $categoryServices): ?>
            <section class="category-section">
                <div class="category-heading">
                    <span class="category-heading-icon">✿</span>
                    <div>
                        <h2><?= spaEscape($category) ?></h2>
                        <p>
                            <?= count($categoryServices) ?>
                            <?= count($categoryServices) === 1 ? "service" : "services" ?>
                        </p>
                    </div>
                </div>

                <div class="service-grid">
                    <?php foreach ($categoryServices as $service): ?>
                        <article class="service-card">
                            <div class="service-card-top">
                                <span class="service-symbol">✧</span>
                                <span class="category-label">
                                    <?= spaEscape($category) ?>
                                </span>
                            </div>

                            <h3><?= spaEscape($service["service_name"]) ?></h3>

                            <p class="service-description">
                                <?php
                                $description = trim($service["description"] ?? "");
                                echo spaEscape(
                                    $description !== ""
                                        ? $description
                                        : "Ask our staff for more information about this service."
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

                            <a
                                class="service-action"
                                href="/DG_Wellness_Spa/register.php"
                            >
                                Register to Book &rarr;
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<section class="booking-banner services-booking-banner">
    <div>
        <span class="eyebrow">PLAN YOUR VISIT</span>
        <h2>Your time for self-care starts here.</h2>
        <p>Register or log in to prepare for online appointment booking.</p>
    </div>

    <a class="button button-light" href="/DG_Wellness_Spa/login.php">
        Log In
    </a>
</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
