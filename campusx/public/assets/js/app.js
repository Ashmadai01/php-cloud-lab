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
    function poll() {
      fetch('/messages/' + convId + '/poll?after=' + lastId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf },
        credentials: 'same-origin'
      }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
        if (!data || !data.messages || !data.messages.length) { delay = Math.min(delay * 1.5, 15000); return; }
        delay = 4000;
        data.messages.forEach(function (m) {
          lastId = Math.max(lastId, m.id);
          var div = document.createElement('div');
          div.className = 'msg ' + (String(m.sender_id) === String(thread.getAttribute('data-me')) ? 'mine' : 'theirs');
          div.setAttribute('data-msg-id', String(m.id));
          var body = document.createElement('div');
          body.textContent = m.body;
          var meta = document.createElement('div');
          meta.className = 'meta';
          meta.textContent = m.sender_name + ' · ' + m.created_at;
          div.appendChild(body);
          div.appendChild(meta);
          box.appendChild(div);
        });
        box.scrollTop = box.scrollHeight;
      }).catch(function () { delay = Math.min(delay * 2, 30000); }).finally(function () {
        setTimeout(poll, delay);
      });
    }
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
