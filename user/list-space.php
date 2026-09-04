<?php

/**
 * AVASTRA — List a Space
 *
 * Converts the earlier static list-space.html wizard into a real,
 * database-backed form. A few honest differences from that static
 * version, because the schema doesn't have matching columns:
 *
 *  - "Booking Type" (require approval / instant) was dropped — there's
 *    no such column on `spaces`, and every booking in this system goes
 *    through the same `status = 'pending'` approval flow regardless.
 *  - "Minimum stay" was dropped — no `min_stay_days` column exists.
 *  - "House rules" is still shown in the UI (Step 3) but isn't saved
 *    anywhere yet. See the note in space-details.php: ask Zaid to add
 *        ALTER TABLE spaces ADD COLUMN house_rules TEXT DEFAULT NULL;
 *    Once that column exists, wire it up in the INSERT below.
 */
$pageTitle = 'List a Space';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db     = Database::getInstance();
$userId = (int) $currentUser['id'];

$categories = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$amenities  = $db->query("SELECT id, name FROM amenities ORDER BY name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId  = (int) ($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $state       = trim($_POST['state'] ?? '');
    $zipCode     = trim($_POST['zip_code'] ?? '');
    $totalSqft   = (int) ($_POST['total_sqft'] ?? 0);
    $maxCapacity = (int) ($_POST['max_capacity'] ?? 10);
    $description = trim($_POST['description'] ?? '');
    $dailyRate   = $_POST['daily_rate'] ?? '';
    $weeklyRate  = $_POST['weekly_rate'] ?? '';
    $monthlyRate = $_POST['monthly_rate'] ?? '';
    $deposit     = $_POST['security_deposit'] ?? '0';
    $amenityIds  = array_map('intval', $_POST['amenities'] ?? []);

    if ($categoryId <= 0)          $errors[] = 'Choose a space type.';
    if ($title === '')             $errors[] = 'Enter a title for your space.';
    if ($address === '')           $errors[] = 'Enter the address.';
    if ($city === '')              $errors[] = 'Enter a city.';
    if ($state === '')             $errors[] = 'Enter a state.';
    if ($zipCode === '')           $errors[] = 'Enter a ZIP / PIN code.';
    if ($totalSqft <= 0)           $errors[] = 'Enter the size in sq ft.';
    if (!is_numeric($dailyRate) || (float) $dailyRate <= 0) $errors[] = 'Enter a valid daily rate.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            $insert = $db->prepare("
                INSERT INTO spaces
                    (owner_id, category_id, title, description, address, city, state, zip_code,
                     total_sqft, max_capacity, daily_rate, weekly_rate, monthly_rate, security_deposit,
                     verification_status, is_active)
                VALUES
                    (:owner_id, :category_id, :title, :description, :address, :city, :state, :zip_code,
                     :total_sqft, :max_capacity, :daily_rate, :weekly_rate, :monthly_rate, :security_deposit,
                     'pending', 1)
            ");
            $insert->execute([
                ':owner_id'         => $userId,
                ':category_id'      => $categoryId,
                ':title'            => $title,
                ':description'      => $description,
                ':address'          => $address,
                ':city'             => $city,
                ':state'            => $state,
                ':zip_code'         => $zipCode,
                ':total_sqft'       => $totalSqft,
                ':max_capacity'     => $maxCapacity ?: 10,
                ':daily_rate'       => $dailyRate,
                ':weekly_rate'      => $weeklyRate !== '' ? $weeklyRate : null,
                ':monthly_rate'     => $monthlyRate !== '' ? $monthlyRate : null,
                ':security_deposit' => $deposit !== '' ? $deposit : 0,
            ]);
            $newSpaceId = (int) $db->lastInsertId();

            if (!empty($amenityIds)) {
                $amenityInsert = $db->prepare("INSERT INTO space_amenities (space_id, amenity_id) VALUES (:space_id, :amenity_id)");
                foreach ($amenityIds as $aId) {
                    $amenityInsert->execute([':space_id' => $newSpaceId, ':amenity_id' => $aId]);
                }
            }

            $db->commit();
            $_SESSION['flash_success'] = "Your listing \"{$title}\" was submitted and is now pending review.";
            header("Location: " . APP_URL . "/user/my-spaces.php");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Something went wrong saving your listing. Please try again.';
        }
    }
}

$unreadNotifCount = 0; // used by topbar.php
?>

<div id="user-main">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>

    <div id="user-content">
        <p class="eyebrow" style="display:block;margin-bottom:8px;">Owner</p>
        <h1 style="font-size:30px;margin:0 0 8px;">List your space</h1>
        <p style="color:rgba(23,32,27,0.65);font-size:14.5px;margin-bottom:28px;">
            Takes about 10 minutes. Your listing goes live after admin review.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="bp-alert error" style="margin-bottom:20px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= implode(' ', array_map('htmlspecialchars', $errors)); ?>
            </div>
        <?php endif; ?>

        <div class="wizard-steps" id="wizardSteps">
            <div class="wizard-step active" data-step="1">
                <div class="wizard-circle"><span class="num">1</span><i class="bi bi-check-lg" style="display:none;"></i></div>
                <span class="wizard-step-label">Space details</span>
            </div>
            <div class="wizard-connector"></div>
            <div class="wizard-step" data-step="2">
                <div class="wizard-circle"><span class="num">2</span><i class="bi bi-check-lg" style="display:none;"></i></div>
                <span class="wizard-step-label">Pricing</span>
            </div>
            <div class="wizard-connector"></div>
            <div class="wizard-step" data-step="3">
                <div class="wizard-circle"><span class="num">3</span><i class="bi bi-check-lg" style="display:none;"></i></div>
                <span class="wizard-step-label">Amenities</span>
            </div>
            <div class="wizard-connector"></div>
            <div class="wizard-step" data-step="4">
                <div class="wizard-circle"><span class="num">4</span><i class="bi bi-check-lg" style="display:none;"></i></div>
                <span class="wizard-step-label">Review &amp; submit</span>
            </div>
        </div>

        <form method="POST" action="" id="listSpaceForm">

            <!-- STEP 1 — Space details -->
            <div class="wizard-panel active" data-panel="1">
                <div class="field mb-3">
                    <label for="f_category">Space type *</label>
                    <select class="form-select" name="category_id" id="f_category">
                        <option value="">Select type</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field mb-3">
                    <label for="f_title">Listing title *</label>
                    <input type="text" class="form-control" name="title" id="f_title" placeholder="e.g. Secure Storage Unit — Andheri East">
                </div>
                <div class="field mb-3">
                    <label for="f_address">Full address *</label>
                    <input type="text" class="form-control" name="address" id="f_address" placeholder="Building name, street, locality">
                    <div class="field-hint">Exact address is shown only to confirmed renters. Search results show locality only.</div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4 field">
                        <label for="f_city">City *</label>
                        <input type="text" class="form-control" name="city" id="f_city" placeholder="e.g. Mumbai">
                    </div>
                    <div class="col-md-4 field">
                        <label for="f_state">State *</label>
                        <input type="text" class="form-control" name="state" id="f_state" placeholder="e.g. Maharashtra">
                    </div>
                    <div class="col-md-4 field">
                        <label for="f_zip">ZIP / PIN code *</label>
                        <input type="text" class="form-control" name="zip_code" id="f_zip" placeholder="e.g. 400069">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6 field">
                        <label for="f_size">Size (sq ft) *</label>
                        <input type="number" class="form-control" name="total_sqft" id="f_size" placeholder="e.g. 2000">
                    </div>
                    <div class="col-md-6 field">
                        <label for="f_capacity">Max capacity (people)</label>
                        <input type="number" class="form-control" name="max_capacity" id="f_capacity" value="10" min="1">
                    </div>
                </div>
                <div class="field mb-3">
                    <label for="f_description">Description</label>
                    <textarea class="form-control" name="description" id="f_description" rows="4" placeholder="Describe the space — construction, condition, what it's suitable for, access instructions…"></textarea>
                </div>
                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top:1px solid rgba(23,32,27,0.08);">
                    <button type="button" class="btn btn-primary-avastra" onclick="goToStep(2, ['f_category','f_title','f_address','f_city','f_state','f_zip','f_size'])">Continue <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 2 — Pricing -->
            <div class="wizard-panel" data-panel="2">
                <div class="row g-3 mb-3">
                    <div class="col-md-4 field">
                        <label for="f_daily">Daily rate (₹) *</label>
                        <input type="number" class="form-control" name="daily_rate" id="f_daily" placeholder="2800">
                    </div>
                    <div class="col-md-4 field">
                        <label for="f_weekly">Weekly rate (₹)</label>
                        <input type="number" class="form-control" name="weekly_rate" id="f_weekly" placeholder="Optional">
                    </div>
                    <div class="col-md-4 field">
                        <label for="f_monthly">Monthly rate (₹)</label>
                        <input type="number" class="form-control" name="monthly_rate" id="f_monthly" placeholder="Optional">
                    </div>
                </div>
                <div class="field mb-3" style="max-width:300px;">
                    <label for="f_deposit">Refundable deposit (₹)</label>
                    <input type="number" class="form-control" name="security_deposit" id="f_deposit" placeholder="e.g. 5000">
                    <div class="field-hint">Shown to renters before they request. Refunded after move-out.</div>
                </div>
                <div class="bp-alert" style="background:rgba(20,92,74,0.06);color:var(--teal);max-width:520px;">
                    <i class="bi bi-info-circle"></i> Every booking request currently needs your approval before it's confirmed — there's no instant-booking option yet.
                </div>
                <div class="d-flex justify-content-between mt-4 pt-3" style="border-top:1px solid rgba(23,32,27,0.08);">
                    <button type="button" class="btn btn-ghost-avastra" onclick="goToStep(1)">Back</button>
                    <button type="button" class="btn btn-primary-avastra" onclick="goToStep(3, ['f_daily'])">Continue <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 3 — Amenities & rules -->
            <div class="wizard-panel" data-panel="3">
                <div class="mb-4">
                    <label style="font-size:12.5px;font-weight:600;text-transform:uppercase;letter-spacing:0.02em;display:block;margin-bottom:6px;">Available amenities</label>
                    <p class="field-hint mb-3">Only select amenities that are actually available. Inaccurate amenity listings may lead to complaints.</p>
                    <div id="amenityChips">
                        <?php foreach ($amenities as $a): ?>
                            <label class="amenity-chip" onclick="toggleAmenity(this)">
                                <input type="checkbox" name="amenities[]" value="<?= $a['id']; ?>" style="display:none;">
                                <i class="bi bi-check-lg"></i><?= htmlspecialchars($a['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="field mb-3">
                    <label for="f_rules">House rules (optional)</label>
                    <textarea class="form-control" id="f_rules" rows="4" placeholder="e.g. No flammable material. No sub-letting. Access allowed 6am–10pm only."></textarea>
                    <div class="field-hint">Note: not saved yet — this needs a small database change first (see comment at the top of this file).</div>
                </div>
                <div class="d-flex justify-content-between mt-4 pt-3" style="border-top:1px solid rgba(23,32,27,0.08);">
                    <button type="button" class="btn btn-ghost-avastra" onclick="goToStep(2)">Back</button>
                    <button type="button" class="btn btn-primary-avastra" onclick="goToStep(4)">Continue <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

            <!-- STEP 4 — Review & submit -->
            <div class="wizard-panel" data-panel="4">
                <div class="review-shell" id="reviewShell"></div>
                <p class="field-hint mt-3">Your listing will be reviewed by the AVASTRA team before going live. You'll be notified within 1–2 working days.</p>
                <div class="d-flex justify-content-between mt-4 pt-3" style="border-top:1px solid rgba(23,32,27,0.08);">
                    <button type="button" class="btn btn-ghost-avastra" onclick="goToStep(3)">Back</button>
                    <button type="submit" class="btn btn-primary-avastra">Submit for review <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>

        </form>
    </div><!-- /#user-content -->

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script>
        let currentStep = 1;

        function toggleAmenity(label) {
            label.classList.toggle('selected');
            label.querySelector('input[type=checkbox]').checked = label.classList.contains('selected');
        }

        function setStepVisual() {
            document.querySelectorAll('.wizard-step').forEach(stepEl => {
                const n = parseInt(stepEl.getAttribute('data-step'), 10);
                stepEl.classList.remove('active', 'done');
                const numSpan = stepEl.querySelector('.num');
                const check = stepEl.querySelector('.bi-check-lg');
                if (n < currentStep) {
                    stepEl.classList.add('done');
                    numSpan.style.display = 'none';
                    check.style.display = 'inline';
                } else if (n === currentStep) {
                    stepEl.classList.add('active');
                    numSpan.style.display = 'inline';
                    check.style.display = 'none';
                } else {
                    numSpan.style.display = 'inline';
                    check.style.display = 'none';
                }
            });
        }

        function showPanel(step) {
            document.querySelectorAll('.wizard-panel').forEach(p => {
                p.classList.toggle('active', parseInt(p.getAttribute('data-panel'), 10) === step);
            });
            window.scrollTo({
                top: document.getElementById('wizardSteps').offsetTop - 100,
                behavior: 'smooth'
            });
        }

        function validateStepFields(ids) {
            let ok = true;
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (!el.value.trim()) {
                    el.style.borderColor = '#8a3324';
                    ok = false;
                } else {
                    el.style.borderColor = '';
                }
            });
            return ok;
        }

        function goToStep(step, requiredIds) {
            if (requiredIds && !validateStepFields(requiredIds)) return;
            currentStep = step;
            setStepVisual();
            showPanel(step);
            if (step === 4) buildReview();
        }

        function val(id) {
            const el = document.getElementById(id);
            return el ? el.value.trim() : '';
        }

        function buildReview() {
            const categoryText = document.getElementById('f_category').selectedOptions[0].text;
            const size = val('f_size');
            const daily = val('f_daily');
            const deposit = val('f_deposit');
            const amenityNames = Array.from(document.querySelectorAll('.amenity-chip.selected'))
                .map(c => c.textContent.trim());

            const rows = [
                ['Space type', categoryText !== 'Select type' ? categoryText : '—'],
                ['Title', val('f_title') || '—'],
                ['Address', val('f_address') || '—'],
                ['City / State', (val('f_city') || '—') + ', ' + (val('f_state') || '—')],
                ['Size', size ? size + ' sq ft' : '—'],
                ['Daily rate', daily ? '₹' + Number(daily).toLocaleString('en-IN') : '—'],
                ['Deposit', deposit ? '₹' + Number(deposit).toLocaleString('en-IN') + ' (refundable)' : '₹0'],
                ['Amenities', amenityNames.length ? amenityNames.join(', ') : 'None selected'],
            ];

            document.getElementById('reviewShell').innerHTML = rows.map(([label, value]) =>
                `<div class="review-row"><span class="rlabel">${label}</span><span class="rvalue">${value}</span></div>`
            ).join('');
        }

        setStepVisual();
    </script>