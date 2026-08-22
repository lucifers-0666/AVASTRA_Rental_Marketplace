<?php
$pageTitle = 'Rent any space, for any duration';
require_once __DIR__ . '/includes/header.php';
?>
<section class="text-center py-5">
  <h1 class="display-5 fw-bold">Unused space, rented on your terms</h1>
  <p class="lead text-muted col-lg-8 mx-auto">
    Rooms, garages, warehouses, shops — monetize idle space or find exactly the space
    you need, for exactly as long as you need it.
  </p>
  <a href="<?= e(url('visitor/browse.php')) ?>" class="btn btn-primary btn-lg me-2">Find a Space</a>
  <a href="<?= e(url('visitor/register.php')) ?>" class="btn btn-outline-secondary btn-lg">List Your Space</a>
</section>

<section class="row g-4 py-4">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title"><i class="bi bi-calendar2-check text-primary"></i> Conflict-Safe Booking</h5>
      <p class="card-text">Overlap detection makes double booking impossible — every request is checked against existing bookings before confirmation.</p>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title"><i class="bi bi-cash-coin text-primary"></i> Flexible Pricing</h5>
      <p class="card-text">Daily, weekly and monthly rates combine automatically — a 12-day rental prices as 1 week + 5 days, never a forced flat rate.</p>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <h5 class="card-title"><i class="bi bi-bullseye text-primary"></i> Requirement Matching</h5>
      <p class="card-text">Describe what you need — size, city, budget, amenities — and get every space scored with a transparent match percentage.</p>
    </div></div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
