// SDUT Swap frontend helpers — safe DOM APIs only (no innerHTML with untrusted data).
(function () {
  'use strict';

  function showToast(message, type) {
    var wrap = document.querySelector('.toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'toast-wrap';
      document.body.appendChild(wrap);
    }
    var el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : '');
    el.textContent = message;
    el.setAttribute('role', 'status');
    wrap.appendChild(el);
    setTimeout(function () { el.remove(); }, 5000);
  }

  // Render server-provided flash messages as toasts (textContent only).
  document.querySelectorAll('[data-flash]').forEach(function (el) {
    showToast(el.getAttribute('data-flash'), el.getAttribute('data-flash-type') || 'success');
  });
  // Global unread message notifications
  var unreadBadge = document.querySelector('[data-unread-badge]');

  if (unreadBadge) {
    var lastNotifiedId = null;

    function checkUnreadMessages() {
      fetch('/messages/unread', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (r) {
        return r.ok ? r.json() : null;
      })
      .then(function (data) {
        if (!data) return;

        var count = Number(data.count || 0);

        unreadBadge.textContent = count > 99 ? '99+' : String(count);
        unreadBadge.hidden = count === 0;

        var latest = data.latest;

        if (!latest) {
          lastNotifiedId = null;
          return;
        }

        var messageId = Number(latest.id);

        if (lastNotifiedId === null) {
          lastNotifiedId = messageId;
          return;
        }

        if (messageId > lastNotifiedId) {
          lastNotifiedId = messageId;

          var wrap = document.querySelector('.toast-wrap');
          if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'toast-wrap';
            document.body.appendChild(wrap);
          }

          var link = document.createElement('a');
          link.className = 'toast';
          link.href = '/messages/' + latest.conversation_id;
          link.textContent = 'New message from ' + latest.sender_name;
          link.setAttribute('role', 'status');
          link.style.color = '#fff';
          link.style.textDecoration = 'none';

          wrap.appendChild(link);

          setTimeout(function () {
            link.remove();
          }, 6000);
        }
      })
      .catch(function () {
        // Retry on the next polling cycle.
      });
    }

    checkUnreadMessages();
    setInterval(checkUnreadMessages, 5000);
  }
  // Mobile nav toggle
  var toggle = document.querySelector('.mobile-toggle');
  var nav = document.querySelector('.main-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', nav.classList.contains('open') ? 'true' : 'false');
    });
  }

  // Confirm dialogs for destructive actions
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      var msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) ev.preventDefault();
    });
  });

  // Gallery thumbnails
  document.querySelectorAll('.thumbs button').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var main = document.querySelector('.gallery-main img');
      if (main) main.src = btn.getAttribute('data-full');
      document.querySelectorAll('.thumbs button').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
    });
  });

  // Chat polling (AJAX polling — not WebSocket)
  var thread = document.querySelector('[data-chat-thread]');
  if (thread) {
    var convId = thread.getAttribute('data-chat-thread');
    var box = document.querySelector('.messages');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';
    var lastId = 0;
    var msgs = box ? box.querySelectorAll('.msg') : [];
    if (msgs.length) lastId = parseInt(msgs[msgs.length - 1].getAttribute('data-msg-id') || '0', 10);
    var delay = 4000;
    var messageInput = document.querySelector('.chat-form input[name="body"]');

    // AJAX CHAT SEND
    var chatForm = document.querySelector('.chat-form');

    if (chatForm && messageInput) {
      chatForm.addEventListener('submit', function (event) {
        event.preventDefault();

        if (!messageInput.value.trim()) return;

        var submitButton = chatForm.querySelector('button[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        var sendingStatus = document.createElement('div');
        sendingStatus.className = 'chat-send-status';
        sendingStatus.textContent = 'Sending...';
        chatForm.appendChild(sendingStatus);

        fetch(chatForm.action, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf
          },
          body: new FormData(chatForm),
          credentials: 'same-origin'
        })
        .then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok || !data.success) {
              throw new Error(data.error || 'Message could not be sent.');
            }
            return data;
          });
        })
        .then(function (data) {
          var m = data.message;
          sendingStatus.textContent = 'Sent';
          lastId = Math.max(lastId, Number(m.id));

          var div = document.createElement('div');
          div.className = 'msg mine';
          div.setAttribute('data-msg-id', String(m.id));

          var body = document.createElement('div');
          body.className = 'message-body';
          body.textContent = m.body;

          var meta = document.createElement('div');
          meta.className = 'meta';
          meta.textContent = m.sender_name + ' · ' + m.created_at;

          var status = document.createElement('span');
          status.className = 'read-status';
          status.setAttribute('data-read-status', String(m.id));
          status.textContent = '✓';

          meta.appendChild(document.createTextNode(' · '));
          meta.appendChild(status);
          div.appendChild(body);
          div.appendChild(meta);
          box.appendChild(div);

          messageInput.value = '';
          box.scrollTop = box.scrollHeight;
        })
        .catch(function (error) {
          sendingStatus.textContent = 'Failed: ' + error.message;
          sendingStatus.classList.add('send-failed');
        })
        .finally(function () {
          if (submitButton) submitButton.disabled = false;
          messageInput.focus();
        });
      });
    }

var typingIndicator = document.getElementById('typing-indicator');
var typingTimer = null;
var lastTypingSent = 0;

if (messageInput) {
  messageInput.addEventListener('input', function () {
    var now = Date.now();

    // Send typing status at most once every 2 seconds.
    if (now - lastTypingSent >= 2000) {
      lastTypingSent = now;

      fetch('/messages/' + convId + '/typing', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': csrf
        },
        credentials: 'same-origin'
      }).catch(function () {});
    }
  });
}
    function poll() {

var isChatVisible = document.visibilityState === 'visible' && document.hasFocus();

fetch('/messages/' + convId + '/poll?after=' + lastId + '&read=' + (isChatVisible ? '1' : '0'), {
  headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf },
  credentials: 'same-origin'
}).then(function (r) {
  return r.ok ? r.json() : null;
}).then(function (data) {
  if (!data) {
    delay = Math.min(delay * 1.5, 15000);
    return;
  }
  var presenceEl = document.getElementById('presence-status');
  if (typingIndicator) {
  typingIndicator.style.display = data.typing ? 'block' : 'none';
}

if (presenceEl && data.presence) {
  if (data.presence.online) {
    presenceEl.innerHTML = '<span style="color:#16a34a">● Online</span>';
  } else if (data.presence.last_seen) {
    presenceEl.textContent = 'Last seen ' + data.presence.last_seen;
  } else {
    presenceEl.textContent = 'Offline';
  }
}

  (data.statuses || []).forEach(function (status) {
    var statusEl = box.querySelector(
      '[data-read-status="' + status.id + '"]'
    );

    if (statusEl) {
      statusEl.textContent = status.is_read ? '✓✓' : (status.is_delivered ? '✓✓' : '✓');
      statusEl.className = 'read-status' + (status.is_read ? ' status-read' : (status.is_delivered ? ' status-delivered' : ''));
    }
  });

  (data.messages || []).forEach(function (m) {
    lastId = Math.max(lastId, Number(m.id));

    var isMine = String(m.sender_id) === String(thread.getAttribute('data-me'));

    var div = document.createElement('div');
    div.className = 'msg ' + (isMine ? 'mine' : 'theirs');
    div.setAttribute('data-msg-id', String(m.id));

    var body = document.createElement('div');
    body.textContent = m.body;

    var meta = document.createElement('div');
    meta.className = 'meta';
    meta.textContent = m.sender_name + ' · ' + m.created_at;

    if (isMine) {
      var statusEl = document.createElement('span');
      statusEl.className = 'read-status';
      statusEl.setAttribute('data-read-status', String(m.id));
      statusEl.textContent = m.is_read ? '✓✓' : (m.is_delivered ? '✓✓' : '✓');
      statusEl.className = 'read-status' + (m.is_read ? ' status-read' : (m.is_delivered ? ' status-delivered' : ''));

      meta.appendChild(document.createTextNode(' · '));
      meta.appendChild(statusEl);
    }

    div.appendChild(body);
    div.appendChild(meta);
    box.appendChild(div);
  });

  delay = 4000;

  if ((data.messages || []).length) {
    box.scrollTop = box.scrollHeight;
  }
}).catch(function () {
  delay = Math.min(delay * 2, 30000);
}).finally(function () {
  setTimeout(poll, delay);
});

    }
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible' && document.hasFocus()) {
        delay = 100;
      }
    });

    window.addEventListener('focus', function () {
      delay = 100;
    });

    setTimeout(poll, delay);
  }


  // Support ticket replies (AJAX — no page reload)
  var ticketForm = document.querySelector('[data-ticket-reply-form]');
  var ticketMessages = document.querySelector('[data-ticket-messages]');

  if (ticketForm && ticketMessages) {
    ticketForm.addEventListener('submit', function (event) {
      event.preventDefault();

      var bodyInput = ticketForm.querySelector('[name="body"]');
      var submitButton = ticketForm.querySelector('button[type="submit"]');

      if (!bodyInput || !bodyInput.value.trim()) return;

      var status = ticketForm.querySelector('[data-ticket-send-status]');

      if (!status) {
        status = document.createElement('div');
        status.setAttribute('data-ticket-send-status', '');
        status.setAttribute('role', 'status');
        status.style.flexBasis = '100%';
        ticketForm.appendChild(status);
      }

      status.textContent = 'Sending...';
      status.classList.remove('send-failed');

      if (submitButton) submitButton.disabled = true;

      fetch(ticketForm.action, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new FormData(ticketForm),
        credentials: 'same-origin'
      })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Reply could not be sent.');
          }
          return data;
        });
      })
      .then(function (data) {
        var m = data.message;

        if (!m) throw new Error('The server did not return the sent message.');

        var div = document.createElement('div');
        div.className = 'msg mine';

        var messageBody = document.createElement('div');
        messageBody.style.whiteSpace = 'pre-wrap';
        messageBody.textContent = m.body || '';
        div.appendChild(messageBody);

        (m.attachments || []).forEach(function (attachmentId) {
          var attachmentUrl = '/support/attachments/' + Number(attachmentId);

          var link = document.createElement('a');
          link.href = attachmentUrl;
          link.target = '_blank';
          link.rel = 'noopener';

          var img = document.createElement('img');
          img.src = attachmentUrl;
          img.alt = 'Attachment';
          img.loading = 'lazy';
          img.style.maxWidth = '160px';
          img.style.maxHeight = '120px';
          img.style.borderRadius = '8px';
          img.style.marginTop = '6px';
          img.style.objectFit = 'cover';

          link.appendChild(img);
          div.appendChild(link);
        });

        var meta = document.createElement('div');
        meta.className = 'meta';
        meta.textContent = (m.author_name || 'You') + ' · ' + (m.created_at || '');
        div.appendChild(meta);

        ticketMessages.appendChild(div);
        ticketMessages.scrollTop = ticketMessages.scrollHeight;

        bodyInput.value = '';

        var fileInput = ticketForm.querySelector('input[type="file"]');
        if (fileInput) fileInput.value = '';

        status.textContent = 'Reply sent.';

        if (data.attachmentErrors && data.attachmentErrors.length) {
          showToast('Some attachments were not saved: ' + data.attachmentErrors.join(' '), 'error');
        }
      })
      .catch(function (error) {
        status.textContent = 'Failed: ' + error.message;
        status.classList.add('send-failed');
      })
      .finally(function () {
        if (submitButton) submitButton.disabled = false;
        bodyInput.focus();
      });
    });
  }


  // My Listings filters and search (external script for CSP compatibility).
  var listingsTable = document.getElementById('myListingsTable');
  if (listingsTable) {
    var listingRows = Array.from(listingsTable.querySelectorAll('tbody tr'));
    var listingSearch = document.getElementById('listingSearch');
    var listingEmpty = document.getElementById('listingNoResults');
    var listingFilters = Array.from(document.querySelectorAll('.listing-filter'));
    var activeListingFilter = 'all';

    function applyListingFilters() {
      var query = listingSearch ? listingSearch.value.trim().toLocaleLowerCase() : '';
      var visibleCount = 0;

      listingRows.forEach(function (row) {
        var status = (row.getAttribute('data-status') || '').trim().toLowerCase();
        var title = (row.getAttribute('data-title') || '').toLocaleLowerCase();
        var matches = (activeListingFilter === 'all' || status === activeListingFilter)
          && title.indexOf(query) !== -1;
        row.hidden = !matches;
        if (matches) visibleCount++;
      });

      if (listingEmpty) listingEmpty.hidden = visibleCount !== 0;
    }

    listingFilters.forEach(function (button) {
      button.addEventListener('click', function () {
        activeListingFilter = (button.getAttribute('data-filter') || 'all').trim().toLowerCase();
        listingFilters.forEach(function (item) {
          item.classList.toggle('is-selected', item === button);
          item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
        });
        applyListingFilters();
      });
    });

    if (listingSearch) listingSearch.addEventListener('input', applyListingFilters);
    applyListingFilters();
  }


  // Search within the saved favorites grid.
  var favoritesSearch = document.getElementById('favoritesSearch');
  if (favoritesSearch) {
    var favoriteCards = Array.from(document.querySelectorAll('[data-favorite-card]'));
    var noFavoriteResults = document.querySelector('[data-favorites-no-results]');
    favoritesSearch.addEventListener('input', function () {
      var query = favoritesSearch.value.trim().toLocaleLowerCase();
      var visible = 0;
      favoriteCards.forEach(function (card) {
        var title = (card.getAttribute('data-title') || '').toLocaleLowerCase();
        var matches = title.indexOf(query) !== -1;
        card.hidden = !matches;
        if (matches) visible++;
      });
      if (noFavoriteResults) noFavoriteResults.hidden = visible !== 0;
    });
  }

  // Favorite toggle (AJAX — update the button without reloading the listing page).
  document.querySelectorAll('[data-favorite-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;

      var previousText = button.textContent;
      button.disabled = true;

      fetch(form.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(form),
        credentials: 'same-origin'
      })
      .then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok || !data.success) {
            throw new Error(data.error || 'Could not update favorites.');
          }
          return data;
        });
      })
      .then(function (data) {
        button.textContent = data.isFavorite ? '★ Saved to favorites' : '☆ Save to favorites';
        button.setAttribute('aria-pressed', data.isFavorite ? 'true' : 'false');
        showToast(data.message || 'Favorites updated.', 'success');

        var favoriteCard = form.closest('[data-favorite-card]');
        if (favoriteCard && !data.isFavorite) {
          var grid = favoriteCard.parentElement;
          favoriteCard.remove();
          var countEl = document.querySelector('[data-favorites-count]');
          var remaining = grid ? grid.querySelectorAll('[data-favorite-card]').length : 0;
          if (countEl) countEl.textContent = String(remaining);
          if (remaining === 0) {
            if (grid) grid.hidden = true;
            var toolbarSearch = document.querySelector('.favorites-search');
            if (toolbarSearch) toolbarSearch.hidden = true;
            var emptyState = document.querySelector('[data-favorites-empty]');
            if (emptyState) emptyState.hidden = false;
            var noResults = document.querySelector('[data-favorites-no-results]');
            if (noResults) noResults.hidden = true;
          }
        }
      })
      .catch(function (error) {
        button.textContent = previousText;
        showToast(error.message || 'Could not update favorites.', 'error');
      })
      .finally(function () {
        button.disabled = false;
      });
    });
  });

  // Client-side file size hint (server enforces limits)
  document.querySelectorAll('input[type="file"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var max = 5 * 1024 * 1024;
      for (var i = 0; i < input.files.length; i++) {
        if (input.files[i].size > max) {
          showToast('File "' + input.files[i].name + '" exceeds 5 MB limit.', 'error');
          input.value = '';
          break;
        }
      }
    });
  });


  // My Support: switch between tickets and reports, with a shared live search.
  var supportTabs = Array.from(document.querySelectorAll('[data-support-tab]'));
  var supportPanes = Array.from(document.querySelectorAll('[data-support-pane]'));
  var supportSearch = document.getElementById('supportRequestSearch');

  function applySupportSearch() {
    var activePane = document.querySelector('[data-support-pane].is-active');
    if (!activePane) return;
    var query = supportSearch ? supportSearch.value.trim().toLocaleLowerCase() : '';
    var items = Array.from(activePane.querySelectorAll('[data-support-item]'));
    var visible = 0;
    items.forEach(function (item) {
      var haystack = (item.getAttribute('data-search') || item.textContent || '').toLocaleLowerCase();
      var matches = haystack.indexOf(query) !== -1;
      item.hidden = !matches;
      if (matches) visible++;
    });
    var empty = activePane.querySelector('[data-support-no-results]');
    if (empty) empty.hidden = visible !== 0;
  }

  supportTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-support-tab');
      supportTabs.forEach(function (item) {
        var selected = item === tab;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-selected', selected ? 'true' : 'false');
        item.tabIndex = selected ? 0 : -1;
      });
      supportPanes.forEach(function (pane) {
        var selected = pane.getAttribute('data-support-pane') === target;
        pane.classList.toggle('is-active', selected);
        pane.hidden = !selected;
      });
      applySupportSearch();
    });
  });

  if (supportSearch) supportSearch.addEventListener('input', applySupportSearch);
  applySupportSearch();

})();


  // Contact Support: live message counter and attachment feedback.
  var supportMessage = document.getElementById('body');
  var supportMessageCount = document.getElementById('supportMessageCount');
  if (supportMessage && supportMessageCount) {
    var updateSupportMessageCount = function () {
      supportMessageCount.textContent = String(supportMessage.value.length);
    };
    supportMessage.addEventListener('input', updateSupportMessageCount);
    updateSupportMessageCount();
  }

  var supportAttachments = document.getElementById('attachments');
  var supportAttachmentHint = document.getElementById('supportAttachmentHint');
  if (supportAttachments && supportAttachmentHint) {
    supportAttachments.addEventListener('change', function () {
      var files = Array.from(supportAttachments.files || []);
      if (files.length > 3) {
        supportAttachments.value = '';
        supportAttachmentHint.textContent = 'Please select no more than 3 images.';
        showToast('You can attach up to 3 images.', 'error');
        return;
      }
      var total = files.reduce(function (sum, file) { return sum + file.size; }, 0);
      var tooLarge = files.find(function (file) { return file.size > 5 * 1024 * 1024; });
      if (tooLarge) {
        supportAttachments.value = '';
        supportAttachmentHint.textContent = 'One of the selected files exceeds 5 MB. Please choose smaller images.';
        return;
      }
      supportAttachmentHint.textContent = files.length
        ? files.length + ' image' + (files.length === 1 ? '' : 's') + ' selected · ' + (total / (1024 * 1024)).toFixed(1) + ' MB total.'
        : 'Attachments help us understand the issue faster. Please hide sensitive information before uploading.';
    });
  }
