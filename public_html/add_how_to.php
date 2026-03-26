
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How To Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         body {
        background: url('img/bg.png') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
    </style>
</head>
<body>
<br>
    <div class="container">
         <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
     </div>
     <?php
include 'conn.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $id = $_POST['id'];  

    if ($id) {
        // Update guide
        $stmt = $conn->prepare("UPDATE howto_guides SET title=?, content=? WHERE id=?");
        $stmt->bind_param("ssi", $title, $content, $id);
    } else {
        // Insert 
        $stmt = $conn->prepare("INSERT INTO howto_guides (title, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $content);
    }

    $stmt->execute();
    header("Location: add_how_to.php"); 
    exit();
}

// Delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM howto_guides WHERE id=?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    header("Location: add_how_to.php"); 
    exit();
}

//  all how-to guides
$result = $conn->query("SELECT * FROM howto_guides");
?>

<div class="container mt-5">
    <h1 class="text-light">Manage 'How To' Guides</h1>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#howToModal">Add New</button>
    
    <div class="list-group">
        <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="list-group-item">
                <h5><?php echo $row['title']; ?></h5>
                <div class="mb-2"><?php echo substr(strip_tags($row['content']), 0, 100); ?>...</div>
                <a href="add_how_to.php?edit=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                <a href="add_how_to.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this guide?')">Delete</a>
            </div>
        <?php } ?>
    </div>
</div>

<!--  Add/Edit -->
<?php
$edit = ['id' => '', 'title' => '', 'content' => ''];

if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM howto_guides WHERE id=?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit = $result->fetch_assoc(); 
    }
}
?>
<div class="modal fade" id="howToModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="add_how_to.php">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo $edit['id'] ? 'Edit' : 'Add'; ?> How To Guide</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $edit['id']; ?>">
          <div class="mb-3">
            <label>Title:</label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($edit['title']); ?>" required>
          </div>
          <div class="mb-3">
            <label>Content:</label>
            <textarea class="form-control" name="content" rows="10" required><?php echo htmlspecialchars($edit['content']); ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-success" type="submit"><?php echo $edit['id'] ? 'Update' : 'Save'; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<?php if (isset($_GET['edit'])): ?>
<script>
  var editModal = new bootstrap.Modal(document.getElementById('howToModal'));
  window.addEventListener('load', function () {
      editModal.show();
      if (history.replaceState) {
          history.replaceState(null, null, 'add_how_to.php');
      }
  });
</script>
<?php endif; ?>


  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
