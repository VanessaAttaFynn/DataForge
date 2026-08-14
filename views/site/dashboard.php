<?php

/** @var \yii\web\View $this */

$this->title = 'Dashboard';
?>
<div class="topbar">
    <div>
        <div class="eyebrow">Welcome back</div>
        <h1 class="page-title">Kwame Asante</h1>
        <div class="page-sub">Level 4 · Computer Science — here's what's happening across DataForge.</div>
    </div>
    <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="text" placeholder="Search datasets, competitions, people…">
    </div>
</div>

<div class="stat-row">
    <div class="stat-card">
        <div class="ring-wrap">
            <svg width="58" height="58" viewBox="0 0 58 58">
                <circle class="ring-bg" cx="29" cy="29" r="24"/>
                <circle class="ring-fg" cx="29" cy="29" r="24" stroke-dasharray="151" stroke-dashoffset="38"/>
            </svg>
            <div class="ring-center">75%</div>
        </div>
        <div class="stat-info">
            <div class="stat-num">Gold</div>
            <div class="stat-label">Current tier</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="ring-wrap">
            <svg width="58" height="58" viewBox="0 0 58 58">
                <circle class="ring-bg" cx="29" cy="29" r="24"/>
                <circle class="ring-fg emerald" cx="29" cy="29" r="24" stroke-dasharray="151" stroke-dashoffset="60"/>
            </svg>
            <div class="ring-center">60%</div>
        </div>
        <div class="stat-info">
            <div class="stat-num">2,340</div>
            <div class="stat-label">Ranking points</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="ring-wrap">
            <svg width="58" height="58" viewBox="0 0 58 58">
                <circle class="ring-bg" cx="29" cy="29" r="24"/>
                <circle class="ring-fg" cx="29" cy="29" r="24" stroke-dasharray="151" stroke-dashoffset="105"/>
            </svg>
            <div class="ring-center">4/10</div>
        </div>
        <div class="stat-info">
            <div class="stat-num">4</div>
            <div class="stat-label">Submissions today</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="ring-wrap">
            <svg width="58" height="58" viewBox="0 0 58 58">
                <circle class="ring-bg" cx="29" cy="29" r="24"/>
                <circle class="ring-fg emerald" cx="29" cy="29" r="24" stroke-dasharray="151" stroke-dashoffset="90"/>
            </svg>
            <div class="ring-center">3</div>
        </div>
        <div class="stat-info">
            <div class="stat-num">3 Active</div>
            <div class="stat-label">Competitions joined</div>
        </div>
    </div>
</div>

<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">Active Competitions & Hackathons</div>
                <div class="panel-link">View all →</div>
            </div>

            <div class="comp-item">
                <div class="comp-icon">🏆</div>
                <div>
                    <div class="comp-name">Crop Yield Prediction Challenge</div>
                    <div class="comp-meta">Dept. of Agric Engineering · Team · 218 entrants</div>
                </div>
                <div class="comp-tag tag-live">Live · 3d left</div>
            </div>

            <div class="comp-item">
                <div class="comp-icon">⚡</div>
                <div>
                    <div class="comp-name">UG FinTech Hackathon 2026</div>
                    <div class="comp-meta">Business School · Individual or Team · 94 entrants</div>
                </div>
                <div class="comp-tag tag-closing">Closes tonight</div>
            </div>

            <div class="comp-item">
                <div class="comp-icon">🧬</div>
                <div>
                    <div class="comp-name">Malaria Cell Classification</div>
                    <div class="comp-meta">Noguchi Institute · Individual · 156 entrants</div>
                </div>
                <div class="comp-tag tag-new">Newly approved</div>
            </div>

            <div class="comp-item">
                <div class="comp-icon">📡</div>
                <div>
                    <div class="comp-name">Campus Traffic Flow Optimization</div>
                    <div class="comp-meta">Dept. of Statistics · Team · 67 entrants</div>
                </div>
                <div class="comp-tag tag-live">Live · 11d left</div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">Trending Datasets</div>
                <div class="panel-link">Browse all →</div>
            </div>
            <div class="dataset-row">
                <div class="dataset-card">
                    <div class="dataset-verified">✓ Verified</div>
                    <div class="dataset-title">Ghana Rainfall 2010–2025</div>
                    <div class="dataset-meta">CSV · 12.4 MB · 340 downloads</div>
                </div>
                <div class="dataset-card">
                    <div class="dataset-verified">✓ Verified</div>
                    <div class="dataset-title">Accra Traffic Sensor Logs</div>
                    <div class="dataset-meta">CSV · 8.1 MB · 212 downloads</div>
                </div>
                <div class="dataset-card">
                    <div class="dataset-verified" style="color: var(--text-faint);">◌ Unverified</div>
                    <div class="dataset-title">Student Sleep Patterns Survey</div>
                    <div class="dataset-meta">CSV · 1.2 MB · 58 downloads</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">Recent Activity</div>
            </div>
            <div class="feed-item">
                <div class="feed-dot"></div>
                <div>
                    <div class="feed-text"><strong>Team Kernel Panic</strong> submitted to Crop Yield Prediction — score 0.891</div>
                    <div class="feed-time">14 MIN AGO</div>
                </div>
            </div>
            <div class="feed-item">
                <div class="feed-dot" style="background: var(--emerald);"></div>
                <div>
                    <div class="feed-text"><strong>Ama Boateng</strong> invited you to join <strong>Team Nyansa</strong></div>
                    <div class="feed-time">1 HR AGO</div>
                </div>
            </div>
            <div class="feed-item">
                <div class="feed-dot"></div>
                <div>
                    <div class="feed-text">Your dataset <strong>Accra Traffic Sensor Logs</strong> was marked Verified</div>
                    <div class="feed-time">3 HRS AGO</div>
                </div>
            </div>
            <div class="feed-item">
                <div class="feed-dot" style="background: var(--rose);"></div>
                <div>
                    <div class="feed-text"><strong>UG FinTech Hackathon</strong> closes in 6 hours — 2 submissions remaining</div>
                    <div class="feed-time">5 HRS AGO</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">My Teams</div>
                <div class="panel-link">Manage →</div>
            </div>
            <div class="team-chip">
                <div class="avatar-stack">
                    <div class="avatar">KA</div>
                    <div class="avatar">AB</div>
                    <div class="avatar">JM</div>
                </div>
                <div class="team-info">
                    <div class="team-name">Team Kernel Panic</div>
                    <div class="team-meta">Crop Yield Challenge · Rank #12</div>
                </div>
            </div>
            <div class="team-chip">
                <div class="avatar-stack">
                    <div class="avatar">KA</div>
                    <div class="avatar">SO</div>
                </div>
                <div class="team-info">
                    <div class="team-name">Team Nyansa (invited)</div>
                    <div class="team-meta">Awaiting your consent</div>
                </div>
            </div>
        </div>
    </div>
</div>