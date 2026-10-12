<?php
/**
 * Clinic Card Component
 * Reusable clinic card for grids and listings
 */

/**
 * Render a single clinic card
 * @param array $clinic Clinic data array
 * @param bool $showRemoveFav Whether to show "Remove from favorites" button
 * @return string HTML
 */
function renderClinicCard($clinic, $showRemoveFav = false) {
    $clName = strtolower($clinic['name']);
    $safeClinicName = sanitize((string)$clinic['name']);
    $isGrooming = str_contains($clName,'groom') || str_contains($clName,'elegance') || str_contains($clName,'paws') || str_contains($clName,'station') || str_contains($clName,'every paw') || str_contains($clName,'d&r');
    $isMixed = (str_contains($clName,'groom') && (str_contains($clName,'vet') || str_contains($clName,'animal') || str_contains($clName,'clinic'))) || str_contains($clName,'every paw') || str_contains($clName,'d&r');
    $clinicIcon = $isGrooming ? 'fa-cut' : 'fa-stethoscope';
    $clinicGrad = $isGrooming
        ? 'linear-gradient(135deg,#F59E0B,#EF4444)'
        : 'linear-gradient(135deg,var(--primary),var(--secondary))';
    $hue = ($clinic['id'] * 37) % 30;
    
    $avgRating = (float)($clinic['avg_rating'] ?? $clinic['average_rating'] ?? 0);
    $ratingCount = (int)($clinic['rcount'] ?? $clinic['total_ratings'] ?? 0);
    
    // Cover photo or placeholder
    $coverPhoto = '';
    $knownCovers = [
        "d&r"          => 'assets/images/clinics/DR.jpg',
        'every'        => 'assets/images/clinics/evey.jpg',
        'nose to tail' => 'assets/images/clinics/NTTVC.jpg',
        'paws & purrs' => 'assets/images/clinics/paws.jpg',
        'paws'         => 'assets/images/clinics/paws.jpg',
        'mjm'          => 'assets/images/clinics/mjm.png',
        'amado'        => 'assets/images/clinics/Amado.jpg',
        'animal life'  => 'assets/images/clinics/Amado.jpg',
        'petdocs'      => 'assets/images/clinics/Petdocs.jpg',
        'petdoctor'    => 'assets/images/clinics/Petdocs.jpg',
        'saint'        => 'assets/images/clinics/saint.png',
        'st. francis'  => 'assets/images/clinics/saint.png',
        'st. martin'   => 'assets/images/clinics/saint.png',
        'francis'      => 'assets/images/clinics/saint.png',
        'martin'       => 'assets/images/clinics/saint.png',
        'rural'        => 'assets/images/clinics/ruralvet.png',
        'elegance'     => 'assets/images/clinics/ruralvet.png',
        'petally'      => 'assets/images/clinics/petally.png',
        'protect'      => 'assets/images/clinics/Protect.png',
    ];
    $matchedCover = null;
    foreach ($knownCovers as $keyword => $path) {
        if (str_contains($clName, $keyword)) { 
            $matchedCover = APP_URL . '/' . $path; 
            break; 
        }
    }

    if (!empty($clinic['cover_photo'])) {
        $imgSrc = str_starts_with($clinic['cover_photo'], 'http')
            ? sanitize($clinic['cover_photo'])
            : APP_URL . '/' . ltrim(sanitize($clinic['cover_photo']), '/');
        $coverPhoto = '<img src="' . $imgSrc . '" alt="" style="width:100%;height:100%;object-fit:cover;">';
    } elseif ($matchedCover) {
        $coverPhoto = '<img src="' . $matchedCover . '" alt="" style="width:100%;height:100%;object-fit:cover;">';
    } else {
        $typeLabel = $isGrooming ? 'Grooming' : 'Veterinary';
        $coverPhoto = <<<HTML
<div style="width:100%;height:100%;background:{$clinicGrad};display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;filter:hue-rotate({$hue}deg);">
  <i class="fas {$clinicIcon}" style="font-size:2.75rem;color:rgba(255,255,255,.65)"></i>
  <span style="font-size:.72rem;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.1em;">{$typeLabel}</span>
</div>
HTML;
    }
    
    // Logo overlay
    $logo = '';
    if (!empty($clinic['logo'])) {
        $logoSrc = str_starts_with($clinic['logo'], 'http')
            ? sanitize($clinic['logo'])
            : APP_URL . '/' . ltrim(sanitize($clinic['logo']), '/');
        $logo = '<img src="' . $logoSrc . '" style="position:absolute;bottom:.75rem;left:.75rem;width:44px;height:44px;border-radius:50%;border:2px solid #fff;object-fit:cover;" alt="logo">';
    } else {
        // Default placeholder logo if none provided
        $logo = '<div style="position:absolute;bottom:.75rem;left:.75rem;width:44px;height:44px;border-radius:50%;border:2px solid #fff;background:#fff;display:flex;align-items:center;justify-content:center;"><i class="fas ' . $clinicIcon . '" style="font-size:1.2rem;color:var(--primary)"></i></div>';
    }
    
    // Phone
    $phone = '';
    if (!empty($clinic['phone'])) {
        $phone = '<div style="font-size:.82rem;color:var(--text-muted);margin-bottom:.6rem;"><i class="fas fa-phone" style="margin-right:.3rem"></i>' . sanitize($clinic['phone']) . '</div>';
    }
    
    // Show services offered, using the service list supplied by the caller.
    $tags = '<div class="clinic-tags">';
    $serviceTags = array_values(array_filter(array_map('trim', explode('||', (string)($clinic['service_tags'] ?? '')))));
    if ($serviceTags) {
        foreach (array_slice($serviceTags, 0, 4) as $serviceTag) {
            $tags .= '<span class="clinic-tag">' . sanitize($serviceTag) . '</span>';
        }
        // Removed "+X more" counter completely
    } else {
        $tags .= '<span class="clinic-tag">Veterinary services</span>';
    }
    $tags .= '</div>';
    
    // Action buttons
    $actions = '';
    if ($showRemoveFav) {
        $viewUrl = APP_URL . '/pages/clinic.php?id=' . $clinic['id'];
        $removeToken = generateCsrfToken();
        $removeId = (int)$clinic['id'];
        $removeAction = APP_URL . '/dashboard/owner/favorites.php';
        $actions = <<<HTML
<div style="display:flex;gap:.4rem;">
  <a href="{$viewUrl}" class="btn btn-sm btn-outline">View</a>
  <form method="POST" action="{$removeAction}" style="display:inline" onsubmit="return confirm('Remove from favorites?')">
    <input type="hidden" name="csrf_token" value="{$removeToken}">
    <input type="hidden" name="remove" value="{$removeId}">
    <button type="submit" class="btn btn-sm btn-danger" aria-label="Remove from favorites"><i class="fas fa-heart-broken"></i></button>
  </form>
</div>
HTML;
    }
    
    $detailUrl = APP_URL . '/pages/clinic.php?id=' . $clinic['id'];
    $city = sanitize($clinic['city'] ?? 'Pangasinan');
    $ratingHtml = starRating($avgRating);
    
    return <<<HTML
<div class="card clinic-card">
  <div class="clinic-card-img" onclick="location.href='{$detailUrl}'">
    {$coverPhoto}
    <span class="clinic-card-badge"><i class="fas fa-star"></i> {$avgRating}</span>
    {$logo}
  </div>
  <div class="clinic-card-body">
  <div class="clinic-card-title" title="{$safeClinicName}">{$safeClinicName}</div>
    <div class="clinic-card-addr"><i class="fas fa-map-marker-alt"></i>{$city}</div>
    {$phone}
    {$tags}
    <div class="clinic-card-footer">
      {$ratingHtml}
      <span style="font-size:.8rem;color:var(--text-muted)">{$ratingCount} reviews</span>
      {$actions}
    </div>
  </div>
</div>
HTML;
}

/**
 * Render a grid of clinic cards
 * @param array $clinics Array of clinic data
 * @param bool $showRemoveFav Whether to show remove from favorites button
 * @return string HTML
 */
function renderClinicsGrid($clinics, $showRemoveFav = false) {
    if (empty($clinics)) {
        return <<<HTML
<div class="empty-state" style="grid-column:1/-1">
  <i class="fas fa-search"></i>
  <h3>No clinics found</h3>
  <p>Try adjusting your search filters.</p>
</div>
HTML;
    }
    
    $html = '<div class="clinics-grid">';
    foreach ($clinics as $clinic) {
        $html .= renderClinicCard($clinic, $showRemoveFav);
    }
    $html .= '</div>';
    return $html;
}
