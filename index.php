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
    }

    elseif ($_POST['action'] == 'delete') {
        $id = $_POST['id'];
        foreach ($topics as $key => $topic) {
            if ($topic->id == $id) {
                array_splice($topics, $key, 1);
                break;
            }
        }

        $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
        file_put_contents($fileName, $JsonString);
    }

 
    elseif ($_POST['action'] == 'comment') {
        $id = $_POST['id'];
        $commentText = trim($_POST['comment']);

        if ($commentText !== '') {
            foreach ($topics as $topic) {
                if ($topic->id == $id) {
                    if (!isset($topic->comments)) {
                        $topic->comments = [];
                    }

                    $topic->comments[] = (object)[
                        "text" => $commentText,
                        "created_at" => date("Y-m-d H:i:s")
                    ];
                    break;
                }
            }

            $JsonString = json_encode($topics, JSON_PRETTY_PRINT);
            file_put_contents($fileName, $JsonString);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum</title>
</head>
<body>

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
    
    echo '<h1>' . htmlspecialchars($selectedTopic->name) . '</h1>';
    echo '<p><strong>Létrehozva:</strong> ' . $selectedTopic->created_at . '</p>';


    echo '<form method="post">
        <input type="hidden" name="id" value="' . $selectedTopic->id . '">
        <input type="hidden" name="action" value="delete">
        <input type="submit" value="Törlés">
    </form>';

    echo '<a href="index.php">Vissza a témákhoz</a>';

   
    echo '<h3>Hozzászólások:</h3>';
    if (!empty($selectedTopic->comments)) {
        echo '<ul>';
        foreach ($selectedTopic->comments as $comment) {
            echo '<li>' . htmlspecialchars($comment->text) .
                 ' <small>(' . $comment->created_at . ')</small></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Nincsenek hozzászólások.</p>';
    }

  
    echo '<form method="post">
        <input type="hidden" name="action" value="comment">
        <input type="hidden" name="id" value="' . $selectedTopic->id . '">
        <input type="text" name="comment" required placeholder="Írj egy hozzászólást...">
        <input type="submit" value="Küldés">
    </form>';

} else {
  
    echo '<h1>Témák:</h1><ol>';

    foreach ($topics as $value) {
        echo '<li>
            <a href="index.php?topic=' . $value->id . '">' . htmlspecialchars($value->name) . '</a>
            <small>(' . (isset($value->created_at) ? $value->created_at : 'nincs dátum') . ')</small>
            <form method="post" style="display:inline;">
                <input type="hidden" name="id" value="' . $value->id . '">
                <input type="hidden" name="action" value="delete">
                <input type="submit" value="Törlés">
            </form>
        </li>';
    }

    echo '</ol>';
}
?>


<form method="POST">
    <input type="hidden" name="action" value="add">
    <input type="text" name="topic" required placeholder="Téma neve">
    <input type="submit" value="Hozzáadás">
</form>

</body>
</html>
