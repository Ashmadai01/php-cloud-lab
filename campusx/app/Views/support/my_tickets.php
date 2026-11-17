<div class="container">
  <div class="page-head"><h1>My Support</h1><p class="muted">Your tickets and reports.</p></div>
  <div class="flex mb"><a class="btn btn-primary" href="<?= url('/support/new') ?>">New ticket</a></div>
  <h2>Tickets</h2>
  <?php if (!empty($tickets)): ?>
    <div class="table-wrap"><table>
      <thead><tr><th>#</th><th>Subject</th><th>Type</th><th>Status</th><th>Priority</th><th>Updated</th></tr></thead>
      <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><a href="<?= url('/support/tickets/' . (int) $t['id']) ?>">#<?= (int) $t['id'] ?></a></td>
          <td><?= e($t['subject']) ?></td>
          <td><?= e(ucfirst($t['ticket_type'])) ?></td>
          <td><span class="badge badge-<?= e($t['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($t['status']))) ?></span></td>
          <td><?= e(ucfirst($t['priority'])) ?></td>
          <td><?= e(timeAgo($t['updated_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?><p class="muted">No tickets yet.</p><?php endif; ?>
  <h2 class="mt">Reports</h2>
  <?php if (!empty($reports)): ?>
    <div class="table-wrap"><table>
      <thead><tr><th>#</th><th>Target</th><th>Type</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
      <?php foreach ($reports as $r): ?>
        <tr>
          <td>#<?= (int) $r['id'] ?></td>
          <td><?= e(ucfirst($r['target_type'])) ?> #<?= (int) $r['target_id'] ?></td>
          <td><?= e($r['report_type']) ?></td>
          <td><span class="badge badge-<?= e($r['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($r['status']))) ?></span></td>
          <td><?= e(timeAgo($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?><p class="muted">No reports submitted.</p><?php endif; ?>
</div>
