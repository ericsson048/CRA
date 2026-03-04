<?php
$pageTitle = 'Notifications';
$activeNav = 'notifications';
$pageSubtitle = 'Suivi des alertes et actions a traiter';
$topActions = '';
require __DIR__ . '/../layout/app_start.php';
?>

<?php if ($allRead): ?><div class="alert alert-success">Toutes les notifications ont ete marquees comme lues.</div><?php endif; ?>

<div class="card">
    <div class="toolbar" style="justify-content: space-between;">
        <h3>Centre de notifications</h3>
        <form method="post" action="notifications.php">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="mark_all_read">
            <button type="submit" class="btn btn-ghost">Tout marquer lu</button>
        </form>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Message</th>
                <th>Date</th>
                <th>Etat</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($notifications)): ?>
            <tr><td colspan="5" class="hint">Aucune notification.</td></tr>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><strong><?= h((string)$notification['title']); ?></strong></td>
                    <td>
                        <?= h((string)$notification['message']); ?>
                        <?php if (!empty($notification['action_url'])): ?>
                            <div><a href="<?= h((string)$notification['action_url']); ?>">Ouvrir</a></div>
                        <?php endif; ?>
                    </td>
                    <td><?= h((string)$notification['created_at']); ?></td>
                    <td><span class="<?= (bool)$notification['is_read'] ? 'badge badge-neutral' : 'badge badge-warn'; ?>"><?= (bool)$notification['is_read'] ? 'Lue' : 'Non lue'; ?></span></td>
                    <td>
                        <?php if (!(bool)$notification['is_read']): ?>
                            <form method="post" action="notifications.php" class="inline">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notification_id" value="<?= (int)$notification['id']; ?>">
                                <button type="submit" class="btn btn-ghost">Marquer lue</button>
                            </form>
                        <?php else: ?>
                            <span class="hint">Aucune action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layout/app_end.php'; ?>
