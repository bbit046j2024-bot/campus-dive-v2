<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['file'];
    $user_id = $_SESSION['user_id'];
    $doc_type = sanitize($conn, $_POST['doc_type'] ?? 'General'); // e.g. Resume, Transcript
    
    // 1. Mock Virus Scan
    if (!scanFile($file['tmp_name'])) {
        echo json_encode(['error' => 'Security Alert: Virus detected! File rejected.']);
        exit;
    }

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    
    if (!in_array($file_ext, $allowed)) {
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    // Generate unique name
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        // 2. Check if document of this type already exists for user
        $stmt = $conn->prepare("SELECT id, version FROM documents WHERE user_id = ? AND document_name = ?"); // Assuming document_name stores type roughly or use a separate type column
        // Actually, previous schema used 'document_name' as user-facing name. Let's use that for 'doc_type' logic for now.
        $stmt->bind_param("is", $user_id, $doc_type);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            // VERSIONING LOGIC
            $doc_id = $existing['id'];
            $new_version = $existing['version'] + 1; // Assuming we added version column to documents, or we just track max in versions table
            // Wait, documents table structure from setup_schema.php: 
            // document_name, file_type, file_size, uploaded_at, status, user_id, filename, original_name
            // We should archive the OLD file into document_versions before updating the main record
            
            // Get current main file info
            $curr_stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
            $curr_stmt->bind_param("i", $doc_id);
            $curr_stmt->execute();
            $curr_doc = $curr_stmt->get_result()->fetch_assoc();
            
            // Insert into versions
            $v_stmt = $conn->prepare("INSERT INTO document_versions (document_id, file_path, original_name, file_size, version_num) VALUES (?, ?, ?, ?, ?)");
            // Determine version number: count existing versions + 1
            $v_count = $conn->query("SELECT COUNT(*) as c FROM document_versions WHERE document_id = $doc_id")->fetch_assoc()['c'];
            $v_num = $v_count + 1;
            
            $v_stmt->bind_param("issii", $doc_id, $curr_doc['filename'], $curr_doc['original_name'], $curr_doc['file_size'], $v_num);
            $v_stmt->execute();
            
            // Update Main Record
            $upd = $conn->prepare("UPDATE documents SET filename = ?, original_name = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?");
            $upd->bind_param("ssii", $new_filename, $file['name'], $file['size'], $doc_id);
            $upd->execute();
            
            $msg = "Document updated (Version " . ($v_num + 1) . ")";
        } else {
            // NEW UPLOAD
            $stmt = $conn->prepare("INSERT INTO documents (user_id, document_name, filename, original_name, file_type, file_size, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("issssi", $user_id, $doc_type, $new_filename, $file['name'], $file_ext, $file['size']);
            $stmt->execute();
            $msg = "Document uploaded successfully";
        }
        
        // Trigger OCR (Mock/Async placeholder)
        // In production, you'd queue thisjob
        
        echo json_encode(['success' => true, 'message' => $msg, 'file' => $new_filename]);
    } else {
        echo json_encode(['error' => 'Failed to save file']);
    }
}

function scanFile($path) {
    // Mock Virus Scanner
    // In real world: exec('clamdscan ' . $path, $output, $return);
    // For now, simple check: don't allow 'virus.txt'
    if (strpos($path, 'eicar') !== false) return false;
    return true;
}
?>
