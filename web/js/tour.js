(function () {
  // Only ever runs once per browser — dismissing or finishing sets this.
  if (localStorage.getItem('df_tour_done') === '1') return;

  var steps = [
    { selector: '[data-tour="dashboard"]', title: 'Dashboard', text: 'Your home base — real stats on what you\'ve joined and published, plus recent activity.' },
    { selector: '[data-tour="datasets"]', title: 'Datasets', text: 'Browse and download datasets shared by the community — verified ones have been checked by a moderator.' },
    { selector: '[data-tour="notebooks"]', title: 'Notebooks', text: 'Read-only Jupyter notebooks — see how others approached a dataset or competition.' },
    { selector: '[data-tour="competitions"]', title: 'Competitions & Hackathons', text: 'Join a challenge, submit predictions, and climb the leaderboard — solo or as a team.' },
    { selector: '[data-tour="participation"]', title: 'My Participation', text: 'Everything you\'ve created or joined, all in one place — including your rank once you\'ve submitted.' },
    { selector: '[data-tour="teams"]', title: 'My Teams', text: 'Create or join a team so you can enter competitions together.' },
    { selector: '[data-tour="mydatasets"]', title: 'My Datasets', text: 'Manage the datasets you\'ve published — published, pending review, and rejected all show up here.' },
    { selector: '[data-tour="mynotebooks"]', title: 'My Notebooks', text: 'Manage the notebooks you\'ve published, same idea as My Datasets.' },
    { selector: '[data-tour="profile"]', title: 'Your Profile', text: 'Click here to open your profile — verify your student ID there, or log out when you\'re done.' },
  ];

  var current = -1;
  var dimEl = null;
  var bubbleEl = null;
  var highlightedEl = null;

  function cleanup() {
    if (highlightedEl) { highlightedEl.classList.remove('tour-highlight'); highlightedEl = null; }
    if (dimEl) { dimEl.remove(); dimEl = null; }
    if (bubbleEl) { bubbleEl.remove(); bubbleEl = null; }
  }

  function finish() {
    cleanup();
    localStorage.setItem('df_tour_done', '1');
  }

  function positionBubble(target) {
    var rect = target.getBoundingClientRect();
    var bubbleWidth = 280;
    var top = Math.max(16, rect.top);
    var left = rect.right + 16;

    // If it would overflow off the right edge, place it to the left instead.
    if (left + bubbleWidth > window.innerWidth - 16) {
      left = Math.max(16, rect.left - bubbleWidth - 16);
    }
    bubbleEl.style.top = top + 'px';
    bubbleEl.style.left = left + 'px';
  }

  function showStep(index) {
    if (highlightedEl) highlightedEl.classList.remove('tour-highlight');

    if (index >= steps.length) { finish(); return; }

    var step = steps[index];
    var target = document.querySelector(step.selector);
    if (!target) { showStep(index + 1); return; } // skip missing elements gracefully

    current = index;
    highlightedEl = target;
    target.classList.add('tour-highlight');

    if (!dimEl) {
      dimEl = document.createElement('div');
      dimEl.className = 'tour-dim';
      document.body.appendChild(dimEl);
    }

    if (!bubbleEl) {
      bubbleEl = document.createElement('div');
      bubbleEl.className = 'tour-bubble';
      document.body.appendChild(bubbleEl);
    }

    var isLast = index === steps.length - 1;
    bubbleEl.innerHTML =
      '<div class="tour-bubble-title">' + step.title + '</div>' +
      '<div class="tour-bubble-text">' + step.text + '</div>' +
      '<div class="tour-bubble-footer">' +
        '<span class="tour-bubble-step">' + (index + 1) + ' / ' + steps.length + '</span>' +
        '<div class="tour-bubble-actions">' +
          '<button type="button" class="tour-btn" id="tour-skip">Skip</button>' +
          '<button type="button" class="tour-btn tour-btn-primary" id="tour-next">' + (isLast ? 'Got it' : 'Next') + '</button>' +
        '</div>' +
      '</div>';

    positionBubble(target);

    document.getElementById('tour-skip').onclick = finish;
    document.getElementById('tour-next').onclick = function () { showStep(index + 1); };
  }

  // Give the page layout a moment to settle before measuring positions.
  window.addEventListener('load', function () {
    setTimeout(function () { showStep(0); }, 400);
  });
})();