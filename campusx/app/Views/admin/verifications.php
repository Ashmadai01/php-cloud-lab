<div class="container">
  <div class="page-head"><h1>Verification Queue</h1><p class="muted">Review Student Card submissions. Card images are private and access is logged.</p></div>
  <nav class="admin-nav"><a href="<?= url('/admin') ?>">Overview</a><a href="<?= url('/admin/users') ?>">Users</a><a href="<?= url('/admin/listings') ?>">Listings</a><a href="<?= url('/admin/verifications') ?>" class="active">Verifications</a><a href="<?= url('/admin/tickets') ?>">Tickets</a><a href="<?= url('/admin/reports') ?>">Reports</a><a href="<?= url('/admin/audit') ?>">Audit Log</a><a href="<?= url('/admin/security') ?>">Security Events</a></nav>
  <?php if (!empty($queue)): ?>
    <div class="table-wrap"><table>
      <thead><tr><th>Student</th><th>University</th><th>Student # (last 4)</th><th>Submitted</th><th>Card</th><th>Decision</th></tr></thead>
      <tbody>
      <?php foreach ($queue as $v): ?>
        <tr>
          <td><?= e($v['full_name']) ?><br><span class="muted small"><?= e($v['user_name']) ?> · <?= e($v['user_email']) ?></span></td>
          <td><?= e($v['university']) ?></td>
          <td>••••<?= e($v['student_number_last4']) ?></td>
          <td><?= e(timeAgo($v['created_at'])) ?></td>
          <td><a class="btn btn-sm btn-outline" href="<?= url('/admin/verifications/' . (int) $v['id'] . '/card') ?>" target="_blank" rel="noopener">View card</a></td>
          <td>
            <form method="post" action="<?= url('/admin/verifications/' . (int) $v['id'] . '/decide') ?>" class="inline-form">
              <?= csrfField() ?>
              <input type="text" name="reason" placeholder="Rejection reason" maxlength="500" style="border:1px solid var(--border);border-radius:8px;padding:5px 8px;width:150px">
              <button class="btn btn-sm btn-primary" name="decision" value="approve" type="submit">Approve</button>
              <button class="btn btn-sm btn-danger" name="decision" value="reject" type="submit" data-confirm="Reject this verification?">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?>
    <div class="empty"><div class="big">✅</div><p>No pending verifications.</p></div>
  <?php endif; ?>
</div>
