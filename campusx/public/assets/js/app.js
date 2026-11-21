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
})();
