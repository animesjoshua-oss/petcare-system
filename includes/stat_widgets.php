<?php
/**
 * Stat Widgets Component
 * Reusable stat cards for dashboard pages
 */

/**
 * Render a single stat widget
 * @param string $value Display value (count/number)
 * @param string $label Description label
 * @param string $icon FontAwesome icon class
 * @param string $color Color theme: blue, orange, green, purple
 * @param string|null $href Optional link URL
 * @return string HTML
 */
function renderStatWidget($value, $label, $icon, $color = 'blue', $href = null) {
    $colorClass = 'sw-' . $color;
    $tag = $href ? 'a' : 'div';
    $hrefAttr = $href ? ' href="' . htmlspecialchars($href) . '"' : '';
    $linkClass = $href ? ' stat-widget-link' : '';
    
    return <<<HTML
<{$tag}{$hrefAttr} class="stat-widget{$linkClass}">
  <div class="stat-widget-icon {$colorClass}"><i class="fas {$icon}"></i></div>
  <div class="stat-widget-info"><strong>{$value}</strong><span>{$label}</span></div>
</{$tag}>
HTML;
}

/**
 * Render a row of stat widgets
 * @param array $stats Array of stat configs: ['value' => '', 'label' => '', 'icon' => '', 'color' => '', 'href' => '']
 * @return string HTML
 */
function renderStatsRow($stats) {
    $html = '<div class="stats-row">';
    foreach ($stats as $stat) {
        $html .= renderStatWidget(
            $stat['value'] ?? '0',
            $stat['label'] ?? '',
            $stat['icon'] ?? 'fa-chart-line',
            $stat['color'] ?? 'blue',
            $stat['href'] ?? null
        );
    }
    $html .= '</div>';
    return $html;
}
