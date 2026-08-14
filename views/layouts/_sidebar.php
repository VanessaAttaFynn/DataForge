<?php

/** @var \yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

// Highlight the active nav item based on the current controller/action route.
$route = Yii::$app->controller->route;

function navActive($routes, $route)
{
    return in_array($route, (array) $routes, true) ? ' active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-mark">DF</div>
        <div>
            <div class="brand-name">DataForge</div>
            <div class="brand-sub">University of Ghana</div>
        </div>
    </div>

    <nav class="nav-group">
        <div class="nav-label">Overview</div>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg> Dashboard',
            ['/site/dashboard'],
            ['class' => 'nav-item' . navActive('site/dashboard', $route)]
        ) ?>
    </nav>

    <nav class="nav-group">
        <div class="nav-label">Explore</div>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.66 3.58 3 8 3s8-1.34 8-3V6"/><path d="M4 12v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6"/></svg> Datasets',
            ['/dataset/index'],
            ['class' => 'nav-item' . navActive('dataset/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 2h6l1 4H8l1-4Z"/><path d="M6 6h12l1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L6 6Z"/><path d="M9 11h6M9 15h4"/></svg> Notebooks',
            ['/notebook/index'],
            ['class' => 'nav-item' . navActive('notebook/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v4a6 6 0 0 1-12 0V3Z"/><path d="M6 5H3v2a3 3 0 0 0 3 3M18 5h3v2a3 3 0 0 1-3 3"/><path d="M12 13v4M8 21h8M9 17h6v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2Z"/></svg> Competitions <span class="nav-badge">3</span>',
            ['/competition/index'],
            ['class' => 'nav-item' . navActive('competition/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg> Hackathons',
            ['/hackathon/index'],
            ['class' => 'nav-item' . navActive('hackathon/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg> Discussions',
            ['/discussion/index'],
            ['class' => 'nav-item' . navActive('discussion/index', $route)]
        ) ?>
    </nav>

    <nav class="nav-group">
        <div class="nav-label">My Space</div>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5S15.5 16.4 15.5 20"/><circle cx="17.5" cy="9" r="2.8"/><path d="M21.5 20c0-2.8-1.9-5-4.5-5.7"/></svg> My Teams',
            ['/team/index'],
            ['class' => 'nav-item' . navActive('team/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 21h8M12 17v4M5 4h14l-1 8a6 6 0 0 1-12 0L5 4Z"/><path d="M5 6H3a2 2 0 0 0 2 4M19 6h2a2 2 0 0 1-2 4"/></svg> Leaderboard',
            ['/leaderboard/index'],
            ['class' => 'nav-item' . navActive('leaderboard/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg> Profile',
            ['/user/profile'],
            ['class' => 'nav-item' . navActive('user/profile', $route)]
        ) ?>
    </nav>

    <nav class="nav-group">
        <div class="nav-label">Admin</div>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg> Approvals',
            ['/approval/index'],
            ['class' => 'nav-item' . navActive('approval/index', $route)]
        ) ?>
        <?= Html::a(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="4"/><path d="M2 21c0-4 3-7 7-7s7 3 7 7"/><path d="M20 8v6M17 11h6"/></svg> Manage Users',
            ['/user/index'],
            ['class' => 'nav-item' . navActive('user/index', $route)]
        ) ?>
    </nav>

    <div class="sidebar-footer">
        <div class="theme-toggle">
            <button type="button" id="btn-dark" class="active" onclick="setTheme('dark')">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 3a9 9 0 1 0 9 9c0-.4 0-.8-.1-1.2A7 7 0 0 1 12 3Z"/></svg>
                Dark
            </button>
            <button type="button" id="btn-light" onclick="setTheme('light')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                Light
            </button>
        </div>
    </div>
</aside>