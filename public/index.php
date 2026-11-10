<?php

// A small task list: create, read, update and delete rows in MySQL with plain
// PHP and PDO. The data lives in the MySQL volume, so it survives redeploys.
require __DIR__.'/db.php';

session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES);
}

function back(string $flash, string $to = '/'): never
{
    $_SESSION['flash'] = $flash;
    header('Location: '.$to, true, 303);
    exit;
}

function task(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

$error = null;
$editing = null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (! hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
            http_response_code(419);
            exit('This form expired. Go back and try again.');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        switch ($_POST['action'] ?? '') {
            case 'create':
                if ($title === '') {
                    back('Give the task a title.');
                }
                db()->prepare('INSERT INTO tasks (title, notes, created_at, updated_at) VALUES (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())')
                    ->execute([mb_substr($title, 0, 200), $notes !== '' ? $notes : null]);
                back('Task added.');
            case 'update':
                if ($title === '') {
                    back('Give the task a title.', "/?edit={$id}");
                }
                db()->prepare('UPDATE tasks SET title = ?, notes = ?, updated_at = UTC_TIMESTAMP() WHERE id = ?')
                    ->execute([mb_substr($title, 0, 200), $notes !== '' ? $notes : null, $id]);
                back('Task saved.');
            case 'toggle':
                db()->prepare('UPDATE tasks SET done = 1 - done, updated_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
                back('Task updated.');
            case 'delete':
                db()->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
                back('Task deleted.');
        }
        back('Unknown action.');
    }

    if (isset($_GET['edit'])) {
        $editing = task((int) $_GET['edit']);
    }
    $tasks = db()->query('SELECT * FROM tasks ORDER BY done, id DESC')->fetchAll();
    $version = (string) db()->query('SELECT VERSION()')->fetchColumn();
} catch (Throwable $exception) {
    http_response_code(500);
    $error = 'Could not reach MySQL: '.$exception->getMessage();
    $tasks = [];
    $version = '?';
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$open = count(array_filter($tasks, fn (array $t) => ! $t['done']));
$csrf = '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tasks · PHP + MySQL</title>
    <style>
        :root { color-scheme: light dark; --line: #e2e8f0; --muted: #64748b; --brand: #16a34a; }
        @media (prefers-color-scheme: dark) { :root { --line: #263241; --muted: #94a3b8; } }
        body { font-family: system-ui, sans-serif; max-width: 42rem; margin: 3rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { margin: 0; } .muted { color: var(--muted); font-size: .9rem; }
        form.card, .error, .flash { border: 1px solid var(--line); border-radius: 12px; padding: 1rem; margin: 1.5rem 0; }
        .flash { border-color: var(--brand); } .error { border-color: #e11d48; color: #e11d48; }
        input[type=text], textarea { width: 100%; box-sizing: border-box; font: inherit; padding: .5rem .6rem; border: 1px solid var(--line); border-radius: 8px; background: transparent; color: inherit; }
        textarea { min-height: 4rem; margin-top: .5rem; }
        button { font: inherit; padding: .4rem .8rem; border-radius: 8px; border: 1px solid var(--line); background: transparent; color: inherit; cursor: pointer; }
        button.primary { background: var(--brand); border-color: var(--brand); color: #fff; margin-top: .6rem; }
        ul { list-style: none; padding: 0; margin: 0; border: 1px solid var(--line); border-radius: 12px; }
        li { display: flex; gap: .75rem; align-items: flex-start; padding: .8rem 1rem; }
        li + li { border-top: 1px solid var(--line); }
        li .body { flex: 1; min-width: 0; } li.done .title { text-decoration: line-through; color: var(--muted); }
        li form { display: inline; } .actions { display: flex; gap: .4rem; } a { color: inherit; }
    </style>
</head>
<body>
    <h1>Tasks</h1>
    <p class="muted"><?= $open ?> open of <?= count($tasks) ?> · stored in MySQL <?= e($version) ?>. Redeploy and they stay.</p>

    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif ?>
    <?php if ($flash): ?><p class="flash"><?= e($flash) ?></p><?php endif ?>

    <?php if ($editing): ?>
        <form class="card" method="post">
            <?= $csrf ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
            <label for="title"><strong>Edit task</strong></label>
            <input id="title" type="text" name="title" maxlength="200" required value="<?= e($editing['title']) ?>">
            <textarea name="notes" aria-label="Notes"><?= e($editing['notes']) ?></textarea>
            <button class="primary" type="submit">Save</button> <a href="/">Cancel</a>
        </form>
    <?php else: ?>
        <form class="card" method="post">
            <?= $csrf ?>
            <input type="hidden" name="action" value="create">
            <label for="title"><strong>New task</strong></label>
            <input id="title" type="text" name="title" maxlength="200" required placeholder="Ship the release notes">
            <textarea name="notes" aria-label="Notes" placeholder="Notes (optional)"></textarea>
            <button class="primary" type="submit">Add task</button>
        </form>
    <?php endif ?>

    <?php if ($tasks): ?>
        <ul>
            <?php foreach ($tasks as $t): ?>
                <li class="<?= $t['done'] ? 'done' : '' ?>">
                    <form method="post">
                        <?= $csrf ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                        <button type="submit" aria-label="<?= $t['done'] ? 'Mark as open' : 'Mark as done' ?>"><?= $t['done'] ? '✓' : '○' ?></button>
                    </form>
                    <div class="body">
                        <div class="title"><?= e($t['title']) ?></div>
                        <?php if ($t['notes']): ?><div class="muted"><?= nl2br(e($t['notes'])) ?></div><?php endif ?>
                    </div>
                    <div class="actions">
                        <a href="/?edit=<?= (int) $t['id'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this task?')">
                            <?= $csrf ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                            <button type="submit">Delete</button>
                        </form>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    <?php elseif (! $error): ?>
        <p class="muted">No tasks yet. Add one above.</p>
    <?php endif ?>
</body>
</html>
