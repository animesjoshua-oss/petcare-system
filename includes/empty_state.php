<?php
/**
 * Empty State Component
 * Reusable empty state for lists, grids, and tables
 */

/**
 * Render an empty state message
 * @param string $icon FontAwesome icon class
 * @param string $title Main heading
 * @param string|null $message Optional description
 * @param string|null $actionText Optional button text
 * @param string|null $actionUrl Optional button URL
 * @param string $padding Optional custom padding (default: 2rem)
 * @return string HTML
 */
function renderEmptyState($icon, $title, $message = null, $actionText = null, $actionUrl = null, $padding = '2rem') {
    $msgHtml = $message ? '<p>' . $message . '</p>' : '';
    $actionHtml = '';
    if ($actionText && $actionUrl) {
        $actionHtml = '<a href="' . htmlspecialchars($actionUrl) . '" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> ' . htmlspecialchars($actionText) . '</a>';
    }
    
    return <<<HTML
<div class="empty-state" style="padding:{$padding};">
  <i class="fas {$icon}"></i>
  <h3>{$title}</h3>
  {$msgHtml}
  {$actionHtml}
</div>
HTML;
}
