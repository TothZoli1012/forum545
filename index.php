<?php
date_default_timezone_set('Europe/Budapest');

$fileName = 'data.json';

if (file_exists($fileName)) {
    $jsonString = file_get_contents($fileName);
    $topics = json_decode($jsonString);
} else {
    $topics = [];
}

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $lastId = 0;
        if (!empty($topics)) {
            $lastItem = end($topics);
            $lastId = $lastItem->id;
        }

        array_push($topics, (object)[
            "id" => $lastId + 1,
            "name" => $_POST['topic'],
            "created_at" => date("Y-m-d H:i:s"),
            "comments" => []
        ]);

        $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
        file_put_contents($fileName, $JsonString);

    } elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        foreach ($topics as $key => $topic) {
            if ($topic->id == $id) {
                array_splice($topics, $key, 1);
                break;
            }
        }

        $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
        file_put_contents($fileName, $JsonString);

    } elseif ($_POST['action'] == 'comment') {
        $id = $_POST['id'];
        $commentText = trim($_POST['comment']);
        $commentName = trim($_POST['name']); 

        if ($commentText !== '' && $commentName !== '') {  
            foreach ($topics as $topic) {
                if ($topic->id == $id) {
                    if (!isset($topic->comments)) {
                        $topic->comments = [];
                    }

                    $topic->comments[] = (object)[
                        "name" => $commentName,
                        "text" => $commentText,
                        "created_at" => date("Y-m-d H:i:s")
                    ];
                    break;
                }
            }

            $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
            file_put_contents($fileName, $JsonString);
        }

    } elseif ($_POST['action'] == 'delete_comment') {
        $topicId = $_POST['topic_id'];
        $commentIndex = $_POST['comment_index'];

        foreach ($topics as $topic) {
            if ($topic->id == $topicId && isset($topic->comments[$commentIndex])) {
                array_splice($topic->comments, $commentIndex, 1);
                break;
            }
        }

        $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
        file_put_contents($fileName, $JsonString);

    } elseif ($_POST['action'] == 'edit_comment') {
        $topicId = $_POST['topic_id'];
        $commentIndex = $_POST['comment_index'];
        $newName = trim($_POST['name']);
        $newText = trim($_POST['comment']);

        if ($newName !== '' && $newText !== '') {
            foreach ($topics as $topic) {
                if ($topic->id == $topicId && isset($topic->comments[$commentIndex])) {
                    $topic->comments[$commentIndex]->name = $newName;
                    $topic->comments[$commentIndex]->text = $newText;
                    $topic->comments[$commentIndex]->created_at = date("Y-m-d H:i:s");
                    break;
                }
            }

            $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
            file_put_contents($fileName, $JsonString);
        }

        header("Location: index.php?topic=" . $topicId);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
<?php
$topicId = $_GET['topic'] ?? null;
$selectedTopic = null;

if ($topicId !== null) {
    foreach ($topics as $topic) {
        if ($topic->id == $topicId) {
            $selectedTopic = $topic;
            break;
        }
    }
}

if ($selectedTopic) {
    echo '<div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title">' . htmlspecialchars($selectedTopic->name) . '</h2>
                <p class="text-muted">Létrehozva: ' . $selectedTopic->created_at . '</p>
                <form method="post" class="mb-3">
                    <input type="hidden" name="id" value="' . $selectedTopic->id . '">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm">Téma 🗑</button>
                </form>
                <a href="index.php" class="btn btn-secondary btn-sm">Vissza</a>
            </div>
          </div>';

    echo '<div class="card mb-4">
            <div class="card-body">
                <h4>Hozzászólások:</h4>';
    if (!empty($selectedTopic->comments)) {
        echo '<ul class="list-group list-group-flush">';
        foreach ($selectedTopic->comments as $index => $comment) {
            $isEditing = (isset($_GET['edit']) && $_GET['edit'] == $index);

            echo '<li class="list-group-item">';

            if ($isEditing) {
           
                echo '<form method="post" class="d-flex flex-column gap-2">';
                echo '<input type="hidden" name="action" value="edit_comment">';
                echo '<input type="hidden" name="topic_id" value="' . $selectedTopic->id . '">';
                echo '<input type="hidden" name="comment_index" value="' . $index . '">';
                echo '<input type="text" name="name" required class="form-control" value="' . htmlspecialchars($comment->name) . '">';
                echo '<input type="text" name="comment" required class="form-control" value="' . htmlspecialchars($comment->text) . '">';
                echo '<div class="d-flex gap-2">';
                echo '<button type="submit" class="btn btn-success btn-sm">Mentés 💾</button>';
                echo '<a href="index.php?topic=' . $selectedTopic->id . '" class="btn btn-secondary btn-sm">Mégse</a>';
                echo '</div>';
                echo '</form>';
            } else {
              
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<div>';
                echo '<strong>' . htmlspecialchars($comment->name) . '</strong>: ' . htmlspecialchars($comment->text);
                echo '<small class="text-muted d-block">' . $comment->created_at . '</small>';
                echo '</div>';
                echo '<div class="ms-2 d-flex gap-1">';
                echo '<a href="index.php?topic=' . $selectedTopic->id . '&edit=' . $index . '" class="btn btn-sm btn-outline-primary">✏️</a>';
                echo '<form method="post">';
                echo '<input type="hidden" name="action" value="delete_comment">';
                echo '<input type="hidden" name="topic_id" value="' . $selectedTopic->id . '">';
                echo '<input type="hidden" name="comment_index" value="' . $index . '">';
                echo '<button type="submit" class="btn btn-sm btn-outline-danger">🗑</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }

            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="text-muted">Nincsenek hozzászólások.</p>';
    }

    echo '</div></div>';

    echo '<div class="card mb-4">
            <div class="card-body">
                <form method="post" class="d-flex gap-2 flex-column flex-sm-row">
                    <input type="hidden" name="action" value="comment">
                    <input type="hidden" name="id" value="' . $selectedTopic->id . '">
                    <input type="text" name="name" required class="form-control" placeholder="Neved">
                    <input type="text" name="comment" required class="form-control" placeholder="Írj egy hozzászólást...">
                    <button type="submit" class="btn btn
                    <button type="submit" class="btn btn-primary">Hozzáadás</button>
                </form>
           </div>
          </div>';

} else {
    echo '<h1 class="mb-4">Témák</h1>';

    echo '<ul class="list-group mb-4">';
    foreach ($topics as $value) {
        echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <a href="index.php?topic=' . $value->id . '" class="fw-bold text-decoration-none">' . htmlspecialchars($value->name) . '</a><br>
                    <small class="text-muted">' . (isset($value->created_at) ? $value->created_at : 'nincs dátum') . '</small>
                </div>
                <form method="post" class="ms-2">
                    <input type="hidden" name="id" value="' . $value->id . '">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                </form>
            </li>';
    }
    echo '</ul>';

    echo '<div class="card">
            <div class="card-body">
                <h5 class="card-title">Új téma hozzáadása</h5>
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="topic" required class="form-control" placeholder="Téma neve">
                    <button type="submit" class="btn btn-success">Hozzáadás</button>
                </form>
            </div>
          </div>';
}
?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
