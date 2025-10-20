<?php
// create_messages_table.php
require_once 'includes/config.php';

try {
    // Create messages table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_table_sql)) {
        echo "✅ Messages table created successfully!<br>";
    } else {
        echo "❌ Error creating messages table: " . $conn->error . "<br>";
    }
    
    // Add sample messages for testing (optional)
    $sample_messages = [
        [1, 2, "Hello! How are you doing?"],
        [2, 1, "I'm good! Thanks for asking. How about you?"],
        [1, 2, "I'm doing great! Just working on some projects."],
        [2, 1, "That sounds interesting! Tell me more about it."]
    ];
    
    $insert_stmt = $conn->prepare("INSERT IGNORE INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
    $inserted_count = 0;
    
    foreach ($sample_messages as $message) {
        $insert_stmt->bind_param("iis", $message[0], $message[1], $message[2]);
        if ($insert_stmt->execute()) {
            $inserted_count++;
        }
    }
    
    echo "✅ Inserted $inserted_count sample messages<br>";
    
    echo "<br>🎉 Database setup completed! <a href='message.php'>Go to Messages</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>