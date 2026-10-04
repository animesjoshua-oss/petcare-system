<?php
/**
 * Pet Card Component
 * Reusable pet profile card for grids and listings
 */

/**
 * Render a single pet card
 * @param array $pet Pet data array with keys: id, name, photo, species, breed, age_years, age_months, gender, weight_kg
 * @param bool $showActions Whether to show action buttons (view, edit, delete)
 * @param string|null $detailUrl Custom detail page URL (default: pet-detail.php?id=X)
 * @return string HTML
 */
function renderPetCard($pet, $showActions = false, $detailUrl = null) {
    $detailUrl = $detailUrl ?? 'pet-detail.php?id=' . $pet['id'];
    $editUrl = 'pets.php?edit=' . $pet['id'];
    $deleteUrl = 'pets.php?delete=' . $pet['id'] . '&csrf=' . generateCsrfToken();
    
    // Pet placeholder emoji
    $emoji = match(strtolower($pet['species'] ?? '')) {
        'cat' => '🐱',
        'dog' => '🐶',
        'bird' => '🐦',
        'rabbit' => '🐰',
        'hamster' => '🐹',
        'fish' => '🐠',
        'reptile' => '🦎',
        default => '🐾'
    };
    
    // Build badges
    $badges = '';
    
    // Age badge - show only years if no months, only months if 0 years
    if ($pet['age_years'] !== null || ($pet['age_months'] ?? 0) > 0) {
        $years = (int)($pet['age_years'] ?? 0);
        $months = (int)($pet['age_months'] ?? 0);
        
        if ($years === 0 && $months > 0) {
            $ageStr = $months . 'm';
        } elseif ($years > 0 && $months === 0) {
            $ageStr = $years . 'y';
        } elseif ($years > 0 && $months > 0) {
            $ageStr = $years . 'y/' . $months . 'm';
        } else {
            $ageStr = ''; // No age data
        }
        
        if ($ageStr) {
            $badges .= '<span class="badge badge-primary">' . $ageStr . '</span>';
        }
    }
    
    // Gender badge
    if (!empty($pet['gender'])) {
        $badges .= '<span class="badge badge-info">' . ucfirst($pet['gender']) . '</span>';
    }
    
    // Weight badge - always show if available
    if (!empty($pet['weight_kg'])) {
        $badges .= '<span class="badge badge-secondary">' . $pet['weight_kg'] . 'kg</span>';
    }
    
    // Action buttons
    $actions = '';
    if ($showActions) {
        $actions = <<<HTML
<div style="display:flex;gap:.5rem;margin-top:.85rem;justify-content:center;">
  <a href="{$detailUrl}" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a>
  <a href="{$editUrl}" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
  <a href="{$deleteUrl}" class="btn btn-sm btn-danger" data-confirm="Delete {$pet['name']}? This cannot be undone."><i class="fas fa-trash"></i></a>
</div>
HTML;
    }
    
    $photoUrl = assetUrl($pet['photo'] ?? null);
    $photo = $photoUrl
        ? '<img src="' . sanitize($photoUrl) . '" alt="' . sanitize($pet['name']) . '">'
        : '<div class="pet-card-placeholder">' . $emoji . '</div>';
    
    $breed = sanitize($pet['breed'] ?: ($pet['species'] ?: 'Unknown'));
    
    return <<<HTML
<div class="card pet-card">
  <div class="pet-card-img" onclick="location.href='{$detailUrl}'">
    {$photo}
  </div>
  <div class="pet-card-body">
    <div class="pet-card-name">{$pet['name']}</div>
    <div class="pet-card-breed">{$breed}</div>
    <div class="pet-card-tags">{$badges}</div>
    {$actions}
  </div>
</div>
HTML;
}

/**
 * Render a grid of pet cards
 * @param array $pets Array of pet data
 * @param bool $showActions Whether to show action buttons
 * @return string HTML
 */
function renderPetsGrid($pets, $showActions = false) {
    if (empty($pets)) {
        return <<<HTML
<div class="card"><div class="card-body">
  <div class="empty-state">
    <i class="fas fa-dog"></i>
    <h3>No pets added yet</h3>
    <p>Add your first pet to start booking appointments and tracking health.</p>
    <a href="?action=add" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Add My First Pet</a>
  </div>
</div></div>
HTML;
    }
    
    $html = '<div class="pets-grid">';
    foreach ($pets as $pet) {
        $html .= renderPetCard($pet, $showActions);
    }
    $html .= '</div>';
    return $html;
}
