<?php
/**
 * Shared Appointment Action Buttons (Approve / Reject / Complete)
 *
 * Required variables before including:
 *   $a          (array)  — appointment row with at least 'id' and 'status'
 *   $apiPath    (string) — relative path to api/appointments.php from current file
 *                          e.g. '../../api/appointments.php'
 *
 * Optional:
 *   $extraActions (string) — additional HTML to append after the action buttons
 */
$apiPath     = $apiPath     ?? '../../api/appointments.php';
$extraActions = $extraActions ?? '';
?>
<div style="display:flex;gap:.35rem;flex-wrap:wrap;">
  <?php if ($a['status'] === 'pending'): ?>
    <form method="POST" action="<?= $apiPath ?>" style="display:inline">
      <?= csrfField() ?>
      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
      <input type="hidden" name="action" value="approve">
      <button class="btn btn-sm btn-secondary" title="Approve"><i class="fas fa-check"></i></button>
    </form>
    <form method="POST" action="<?= $apiPath ?>" style="display:inline">
      <?= csrfField() ?>
      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
      <input type="hidden" name="action" value="reject">
      <button class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
    </form>
  <?php endif; ?>
  <?php if ($a['status'] === 'approved'): ?>
    <form method="POST" action="<?= $apiPath ?>" style="display:inline">
      <?= csrfField() ?>
      <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
      <input type="hidden" name="action" value="complete">
      <button class="btn btn-sm btn-primary" title="Mark Complete">
        <i class="fas fa-check-double"></i>
      </button>
    </form>
  <?php endif; ?>
  <?= $extraActions ?>
</div>
