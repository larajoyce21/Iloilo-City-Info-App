<?php
// Database connection
$db = new mysqli('localhost', 'root', '', 'app');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if (isset($_GET['id'])) {
    $document_id = (int)$_GET['id'];
    $result = $db->query("SELECT * FROM service_documents WHERE id = $document_id");
    
    if ($result->num_rows > 0) {
        $document = $result->fetch_assoc();
        
        if (file_exists($document['file_path'])) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $document['document_name'] . '.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($document['file_path']));
            readfile($document['file_path']);
            $db->close();
            exit;
        } else {
            $db->close();
            die('File not found.');
        }
    } else {
        $db->close();
        die('Document not found.');
    }
} else {
    $db->close();
    die('Invalid request.');
}
?>